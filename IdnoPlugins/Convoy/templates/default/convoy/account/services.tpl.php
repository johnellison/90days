
<div class="row">
    <div class="span10 offset1">
        <?= $this->draw('account/menu') ?>
        <div id="service-placeholder"></div>
        <iframe width="100%" src="<?=\Idno\Core\site()->config()->getDisplayURL()?>withknown/settings" style="border: none; height: 2000px; overflow: hidden; margin-top: -3em;" scrolling="no" allowtransparency="true" ></iframe>
    </div>
</div>
