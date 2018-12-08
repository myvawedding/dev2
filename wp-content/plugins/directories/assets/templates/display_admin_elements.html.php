<div class="drts-display-elements">
    <div class="drts-row">
<?php foreach (array_keys($element_types) as $type):?>
<?php     foreach ($elements[$type] as $element_name => $element):?>
        <div class="drts-col-6 drts-col-md-4">
            <a href="#" class="drts-display-element drts-bs-btn drts-bs-btn-light" data-element-name="<?php echo $element_name;?>" data-element-type="<?php echo $element['type'];?>" data-element-type-label="<?php echo $this->H($element_types[$element['type']]);?>" data-element-label="<?php echo $this->H($element['label']);?>">
                <span class="drts-display-element-label"><?php if (isset($element['icon'])):?><i class="fa-fw <?php echo $element['icon'];?>"></i><?php endif;?><?php echo $this->H($element['label']);?></span>
                <span class="drts-display-element-description"><?php echo $this->H($element['description']);?></span>
            </a>
        </div>
<?php     endforeach;?>
<?php endforeach;?>  
    </div>
</div>
