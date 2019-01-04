<?php if (!empty($settings['other']['not_found']['custom'])):?>
<?php   echo $this->Htmlize($settings['other']['not_found']['html']);?>
<?php else:?>
<p><?php echo $this->H(__('Sorry, there were no items that matched your criteria.', 'directories'));?></p>
<?php endif;?>