<?= $this->draw('entity/edit/header'); ?>
<?php

    /* @var \Idno\Core\Template $this */

    if (!empty($vars['object'])) {
        $title = $vars['object']->getTitle();
        $body = $vars['object']->body;
        $forward_url = $vars['object']->forward_url;
        $hide_title = $vars['object']->hide_title;
    }

    if ($title == 'Untitled') {
        $title = '';
    }

?>
    <form action="<?= $vars['object']->getURL() ?>" method="post" >

        <div class="row">

            <div class="span8 offset2 edit-pane">


                <?php

                    if (empty($vars['object']->_id)) {

                        ?>
                        <h4>New Page</h4>
                    <?php

                    } else {

                        ?>
                        <h4>Edit Page</h4>
                    <?php

                    }

                ?>
                <p>
                    <label>
                        Title<br/>
                        <input type="text" name="title" id="title" placeholder="Give it a title"
                               value="<?= htmlspecialchars($title) ?>" class="span8"/>
                    </label>
                </p>

                <p>
                    <small><a href="#" onclick="$('#moreoptions').show(); return false;">See more options</a></small>
                </p>
                <div id="moreoptions" <?php
                    if (empty($hide_title) && empty($forward_url)) {
                        ?>
                        style="display:none"
                    <?php
                    }
                        ?>>

                    <p>
                        <label>
                            Forward URL<br/>
                            <small>Most of the time, you should leave this blank. Include a URL here if you want users to
                                be forwarded to an external page instead of displaying page content.</small><br>
                            <input type="text" name="forward_url" id="forward_url" placeholder="Website to forward users to"
                                   value="<?= htmlspecialchars($forward_url) ?>" class="span8"/>
                        </label>
                        <label>
                            Show the page title as a heading?
                            <select name="hide_title" >
                                <option value="0">Yes</option>
                                <option value="1" <?php

                                    if (!empty($hide_title)) {
                                        echo 'selected';
                                    }

                                ?>>No</option>
                            </select>
                        </label>
                    </p>

                </div>

                <div class="pages span3">
                    <label>
                        Body </label>  
                </div>
                                     
                <p style="text-align: right">
                    <small>
                        <a href="#" onclick="tinymce.EditorManager.execCommand('mceRemoveEditor',true, 'body'); $('#plainTextSwitch').hide(); $('#richTextSwitch').show(); return false;" id="plainTextSwitch">Switch to plain text editor</a>
                        <a href="#" onclick="makeRich('#body'); $('#plainTextSwitch').show(); $('#richTextSwitch').hide(); return false;" id="richTextSwitch" style="display:none">Switch to rich text editor</a></small></p>
                </p>
                    
                        <textarea name="body" id="body" placeholder="Tell your story"
                                  class="span8 bodyInput mentionable wysiwyg"><?= htmlspecialchars($this->autop($body)) ?></textarea>

                    
               

                <?=$this->draw('entity/tags/input');?>
                
                <p>
                    <label>
                        Parent category<br>
                        <select name="category" class="selectpicker">
                            <option <?php if ($vars['category'] == 'No Category') { echo 'selected'; } ?>>No Category</option>
                            <?php

                                if (!empty($vars['categories'])) {
                                    foreach($vars['categories'] as $category) {

                            ?>
                                        <option <?php if ($category == $vars['category']) { echo 'selected'; } ?>><?=htmlspecialchars($category)?></option>
                            <?php

                                    }
                                }

                            ?>
                        </select>
                    </label>
                </p>

                <p class="button-bar " style="text-align: right">
                    <?= \Idno\Core\site()->actions()->signForm('/staticpages/edit') ?>
                    <input type="button" class="btn btn-cancel" value="Cancel" onclick="hideContentCreateForm();"/>
                    <input type="submit" class="btn btn-primary" value="Publish"/>
                    <?= $this->draw('content/access'); ?>
                </p>

            </div>

        </div>
    </form>
    <script>

        /*function postForm() {
         var content = $('textarea[name="body"]').html($('#body').html());
         console.log(content);
         return content;
         }*/

        $(document).ready(function () {
            makeRich('#body');
        })
        ;

        function makeRich(container) {
            $(container).tinymce({
                selector: 'textarea',
                theme: 'modern',
                skin: 'light',
                statusbar: false,
                menubar: false,
                toolbar: 'styleselect | bold italic | link image | blockquote bullist numlist | alignleft aligncenter alignright | code',
                plugins: 'code link image autoresize',
                relative_urls : false,
                remove_script_host : false,
                convert_urls : true,
                file_picker_callback: function (callback, value, meta) {
                    filePickerDialog(callback, value, meta);
                }
            });
        }

        function filePickerDialog(callback, value, meta) {
            tinymce.activeEditor.windowManager.open({
                title: 'File Manager',
                url: '<?=\Idno\Core\site()->config()->getDisplayURL()?>file/picker/?type=' + meta.filetype,
                width: 650,
                height: 550
            }, {
                oninsert: function (url) {
                    callback(url);
                }
            });
        }
        
        //$('.selectpicker').selectpicker();

    </script>
<?= $this->draw('entity/edit/footer'); ?>