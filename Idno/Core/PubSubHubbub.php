<?php

    /**
     * PubSubHubbub publishing
     *
     * @package idno
     * @subpackage core
     */

    namespace Idno\Core {

        use Idno\Common\Entity;

        class PubSubHubbub extends \Idno\Common\Component
        {

            function init()
            {

            }

            function registerEventHooks()
            {

                // Hook into the "saved" event to publish to PuSH when an entity is saved
                \Idno\Core\site()->addEventHook('saved', function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    if ($object = $eventdata['object']) {
                        /* @var \Idno\Common\Entity $object */
                        if ($object instanceof \Idno\Entities\ActivityStreamPost && $object->isPublic()) {
                            \Idno\Core\PubSubHubbub::publish($object);
                        }
                    }
                });

                // Add PuSH headers to the top of the page
                \Idno\Core\site()->addEventHook('page/head', function (Event $event) {

                    if (!empty(site()->config()->hub)) {
                        $eventdata = $event->data();
                        header('Link: <' . site()->config()->hub . '>; rel="hub"', false);
                        header('Link: <' . site()->template()->getCurrentURL() . '>; rel="self"', false);
                    }

                });

                // When we follow a user, try and subscribe to their hub
                \Idno\Core\site()->addEventHook('follow', function (\Idno\Core\Event $event) {

                    $eventdata = $event->data();
                    $user      = $eventdata['user'];
                    $eventdata = $event->data();
                    $following = $eventdata['following'];

                    if (($user instanceof \Idno\Entities\User) && ($following instanceof \Idno\Entities\User)) {

                        $url = $following->getURL();

                        // Find self reference from profile url
                        if ($feed = $this->findSelf($url)) {
                            $following->pubsub_self = $feed;

                            if ($hubs = $this->discoverHubs($url)) {

                                $pending = unserialize($user->pubsub_pending);
                                if (!$pending)
                                    $pending = new \stdClass ();
                                if (!is_array($pending->subscribe))
                                    $pending->subscribe = array();

                                $pending->subscribe[] = $following->getUUID();
                                $user->pubsub_pending = serialize($pending);
                                $user->save();

                                $following->pubsub_hub = $hubs[0];
                                $following->save();

                                $return = \Idno\Core\Webservice::post($following->pubsub_hub, array(
                                    'hub.callback' => \Idno\Core\site()->config->url . 'pubsub/callback/' . $user->getID() . '/' . $following->getID(), // Callback, unique to each subscriber
                                    'hub.mode'     => 'subscribe',
                                    'hub.verify'   => 'async', // Backwards compatibility with v0.3 hubs
                                    'hub.topic'    => $feed, // Subscribe to rss
                                ));

                                \Idno\Core\site()->logging->log("Pubsub: " . print_r($return, true));
                            } else
                                \Idno\Core\site()->logging->log("Pubsub: No hubs found");
                        }
                    }
                });

                // Send unfollow notification to their hub
                \Idno\Core\site()->addEventHook('unfollow', function (\Idno\Core\Event $event) {

                    $eventdata = $event->data();
                    $user      = $eventdata['user'];
                    $eventdata = $event->data();
                    $following = $eventdata['following'];

                    if (($user instanceof \Idno\Entities\User) && ($following instanceof \Idno\Entities\User)) {

                        $url = $following->getURL();

                        $pending = unserialize($user->pubsub_pending);
                        if (!$pending)
                            $pending = new \stdClass ();
                        if (!is_array($pending->subscribe))
                            $pending->unsubscribe = array();

                        $pending->unsubscribe[] = $following->getID();
                        $user->pubsub_pending   = serialize($pending);
                        $user->save();

                        $return = \Idno\Core\Webservice::post($following->pubsub_hub, array(
                            'hub.callback' => \Idno\Core\site()->config->url . 'pubsub/callback/' . $user->getID() . '/' . $following->getID(), // Callback, unique to each subscriber
                            'hub.mode'     => 'unsubscribe',
                            'hub.verify'   => 'async', // Backwards compatibility with v0.3 hubs
                            'hub.topic'    => $following->pubsub_self
                        ));

                        \Idno\Core\site()->logging->log("Pubsub: " . print_r($return, true));
                    }
                });
            }

            function registerPages()
            {
                // Create an endpoint for subscription pings
                \Idno\Core\site()->addPageHandler('/pubsub/callback/([A-Za-z0-9]+)/([A-Za-z0-9]+)/?', '\Idno\Pages\Pubsubhubbub\Callback');

            }

            /**
             * Find all hub urls for a given url, by looking at its feeds.
             * @param $url the URL of the page to check
             * @param $page optionally, the contents of the page at $url
             * @todo replace this with xpath.
             */
            private function discoverHubs($url, $page = '')
            {

                $hubs = array();

                // Get page, if necessary
                if (empty($page)) {
                    $page = \Idno\Core\Webservice::file_get_contents($url);
                }

                // Find the feed in page
                $feed = $this->findFeed($url, $page);

                // See if we have a hub link in the main page
                if (preg_match_all('/<link href=["\']{1}([^"]+)["\']{1} rel=["\']{1}hub["\']{1}[\s]*\/?>/i', $page, $match)) {
                    $hubs = array_merge($match[1]);
                }
                if (preg_match_all('/<link rel=["\']{1}hub["\']{1} href=["\']{1}([^"]+)["\']{1}[\s]*\/?>/i', $page, $match)) {
                    $hubs = array_merge($match[1]);
                }

                if ($feed) {

                    $page = \Idno\Core\Webservice::file_get_contents($feed);

                    // We may be looking on a feed
                    if (preg_match_all('/<atom:link href=["\']{1}([^"]+)["\']{1} rel=["\']{1}hub["\']{1}[\s]*\/?>/i', $page, $match)) {
                        $hubs = array_merge($match[1]);
                    }
                    if (preg_match_all('/<atom:link rel=["\']{1}hub["\']{1} href=["\']{1}([^"]+)["\']{1}[\s]*\/?>/i', $page, $match)) {
                        $hubs = array_merge($match[1]);
                    }
                }

                if (count($hubs))
                    return $hubs;

                return false;
            }

            /**
             * Find the (first) feed on a given URL.
             * @param type $url
             * @return type
             */
            private function findFeed($url, $data = null)
            {
                $feed = null;

                if (!$data)
                    $data = \Idno\Core\Webservice::file_get_contents($url);

                // search for all 'RSS Feed' declarations
                if (preg_match_all('#<link[^>]+type="application/rss\+xml"[^>]*>#is', $data, $rawMatches)) {

                    if (preg_match('#href="([^"]+)"#i', $rawMatches[0][0], $rawUrl)) {
                        $feed = $rawUrl[1];
                    }

                }

                return $feed;
            }

            /**
             * Find the self resource.
             * This method will find a link self on a feed, finding the feed first
             * @param type $url
             */
            private function findSelf($url)
            {

                $self = null;
                $feed = null;

                // Find RSS
                $feed = $this->findFeed($url);

                // Find self
                if ($feed) {
                    $data = \Idno\Core\Webservice::file_get_contents($feed);

                    if (preg_match('/<atom:link[^>]+href="([^"]+)"[^>]*rel="self"[^>]*>/i', $data, $match)) {
                        $self = $match[1];
                    }
                    if (preg_match('/<atom:link[^>]+rel="self"[^>]*href="([^"]+)"[^>]*>/i', $data, $match)) {
                        $self = $match[1];
                    }
                }

                return $self;
            }

            /**
             * If this Known installation has a PubSubHubbub hub, send a publish notification to the hub
             * @param ActivityStreamPost $act_stream_post
             * @return array
             */
            static function publish($act_stream_post)
            {
                if ($hub = \Idno\Core\site()->config()->hub) {
                    $object = $act_stream_post->getObject();
                    $base   = \Idno\Core\site()->config()->getDisplayURL();
                    $feeds  = array();

                    // Check to make sure there's an entity
                    if (!($object instanceof Entity)) {
                        return false;
                    }

                    // homepage feed
                    $homepage_types = \Idno\Core\site()->config()->getHomepageContentTypes();
                    $type_in_homepage = false;
                    if ($object instanceof Entity) {
                        if (in_array($object->getContentType(), $homepage_types)) {
                            $type_in_homepage = true;
                        }
                    }
                    if (empty($homepage_types) || $type_in_homepage) {
                        $feeds[] = $base;
                    }

                    // type-specific feeds
                    $feeds[] = $base . 'content/' . $object->getContentTypeCategorySlug() . '/';
                    $feeds[] = $base . 'content/all/';

                    // tag feeds
                    foreach ($object->getTags() as $tag) {
                        $feeds[] = $base . 'tag/' . $tag;
                    }

                    if (!empty($feeds)) {
                        // encode the feeds and their RSS counterparts
                        $encurls = array();
                        foreach ($feeds as $feed) {
                            $encurls[] = urlencode($feed);
                            $encurls[] = urlencode(
                                \Idno\Core\site()->template()->getURLWithVar('_t', 'rss', $feed));
                        }

                        $formdata = 'hub.mode=publish&hub.url=' . implode(',', $encurls);
                        \Idno\Core\site()->logging()->log('Pinging ' . $hub . ' with data ' . $formdata);
                        \Idno\Core\Webservice::post($hub, $formdata, array(
                            'Content-Type' => 'application/x-www-form-urlencoded'));
                    }

                    return true;
                }

                return false;
            }

        }

    }