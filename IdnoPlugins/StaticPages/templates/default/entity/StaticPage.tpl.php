
    <div>
        <?php

            if ($vars['object']->canEdit()) {

        ?>
       <div class="row">
        <div class="span10" style="text-align: right;">
        <span>
            <i class="icon-cog"></i> <a href="<?=\Idno\Core\site()->config()->getURL()?>admin/staticpages/">Manage pages</a>
        </span>
	    <?php

    if ($vars['object']->canEdit()) {

?>

        <span style="padding-left: 25px;"><icon class="icon-pencil"></icon> <a href="<?=$vars['object']->getEditURL()?>" class="edit">Edit</a></span>
        <span style="padding-left: 25px;"><icon class="icon-trash"></icon>
        <?=  \Idno\Core\site()->actions()->createLink($vars['object']->getDeleteURL(), 'Delete', array(), array('method' => 'POST', 'class' => 'edit', 'confirm' => true, 'confirm-text' => 'Are you sure you want to permanently delete this entry?'));?></span>

<?php

    }

?>	    	    
    </div>
        </div>
        
        
        <?php

            }

            if (empty($vars['object']->hide_title)) {
        ?>
        <h1 class="p-name" style="margin-bottom: 1em"><?=$vars['object']->getTitle()?></h1>
        <?php
            }

            if (!empty($vars['object']->forward_url)) {

                ?>
                <h2>
                    You are seeing this page because you are a site administrator. Other users will be forwarded
                    to <a href="<?=$vars['object']->forward_url?>"><?=$vars['object']->forward_url?></a>.
                </h2>
                <?php

            }

        ?>
        <?php echo $this->autop($this->parseURLs($this->parseHashtags($vars['object']->body),$rel)); ?>
        
<!--<div class="row" style="margin-top: 3em;">
	<div class="span8">
        <p>
            <a href="<?=\Idno\Core\site()->config()->getURL()?>admin/staticpages/" class="btn btn-primary btn-add">Manage pages</a>
        </p>
        </div>
		<div class="span2" style="text-align: right; padding-top: 8px;">
	    <?php

    if ($vars['object']->canEdit()) {

?>

        <span><icon class="icon-pencil"></icon> <a href="<?=$vars['object']->getEditURL()?>" class="edit">Edit</a></span>
        <span style="padding-left: 35px;"><icon class="icon-trash"></icon>
        <?=  \Idno\Core\site()->actions()->createLink($vars['object']->getDeleteURL(), 'Delete', array(), array('method' => 'POST', 'class' => 'edit', 'confirm' => true, 'confirm-text' => 'Are you sure you want to permanently delete this entry?'));?></span>

<?php

    }

?>	    	    
    </div>
        </div>-->
    </div>
