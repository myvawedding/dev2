<div class="drts-payment-pricing-table drts-payment-pricing-table-<?php echo $layout;?> <?php echo DRTS_BS_PREFIX;?>card-<?php echo $layout;?><?php if ($has_featured):?> drts-payment-pricing-table-has-featured<?php endif;?>">
<?php foreach ($plans as $plan_id => $plan):?>
    <div data-plan-id="<?php echo $plan_id;?>" class="drts-payment-plan<?php if ($plan['featured']):?> drts-payment-plan-featured<?php endif;?> <?php echo DRTS_BS_PREFIX;?>card <?php echo DRTS_BS_PREFIX;?>text-center<?php if ($plan['featured']):?> drts-payment-plan-featured <?php echo DRTS_BS_PREFIX;?>border-<?php echo $featured_border_color;?><?php endif;?>">
        <div class="drts-payment-plan-header <?php echo DRTS_BS_PREFIX;?>card-header<?php if ($plan['featured']):?> <?php echo DRTS_BS_PREFIX;?>bg-<?php echo $featured_bg_color;?> <?php echo DRTS_BS_PREFIX;?>text-<?php echo $featured_text_color;?><?php endif;?>">
            <?php echo $this->H($plan['title']);?>
        </div>
        <div class="drts-payment-plan-body <?php echo DRTS_BS_PREFIX;?>card-body">
            <h2 class="<?php echo DRTS_BS_PREFIX;?>card-title">
                <?php echo $plan['price'];?>
            </h2>
<?php if (strlen($plan['description'])):?>
            <p class="<?php echo DRTS_BS_PREFIX;?>card-text <?php echo DRTS_BS_PREFIX;?>text-muted"><?php echo $this->Htmlize($plan['description'], true);?></p>
<?php endif;?>
        </div>
        <div class="drts-payment-plan-features <?php echo DRTS_BS_PREFIX;?>list-group <?php echo DRTS_BS_PREFIX;?>list-group-flush">
<?php   for ($i = 0; $i < $max_feature_count; ++$i):?>
            <div class="<?php echo DRTS_BS_PREFIX;?>list-group-item"><?php if ($feature_html = array_shift($plan['features'])):?><?php echo $feature_html;?><?php else:?>&nbsp;<?php endif;?></div>
<?php   endfor;?>
        </div>
        <div class="drts-payment-plan-footer <?php echo DRTS_BS_PREFIX;?>card-footer<?php if ($plan['featured']):?> <?php echo DRTS_BS_PREFIX;?>bg-<?php echo $featured_bg_color;?><?php endif;?>">
            <a href="<?php echo $plan['order_url'];?>" class="<?php echo DRTS_BS_PREFIX;?>btn <?php echo DRTS_BS_PREFIX;?>btn-<?php if ($plan['featured']):?><?php echo $featured_btn_color;?><?php else:?><?php echo $btn_color;?><?php endif;?>"><?php echo $this->H($btn_text);?></a>
        </div>
    </div>
<?php endforeach;?>
</div>