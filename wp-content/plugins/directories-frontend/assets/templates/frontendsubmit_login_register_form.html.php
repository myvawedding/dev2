<?php
$form = $this->Form_Render($form);
$html = $form->getHtml(true);
$is_two_columns = isset($html['register']) || isset($html['guest']);
$hidden_html = $form->getHiddenHtml();
$js_html = $form->getJsHtml(false);
?>
<?php echo $form->getHeaderHtml();?>
<?php echo $form->getFormTag();?>
    <div class="drts-frontendsubmit-login-register-form-column">
        <div class="drts-frontendsubmit-login-form">
            <h2><?php echo $this->H(__('Login', 'directories-frontend'));?></h2>
            <?php echo $html['login'];?>
        </div>
    </div>
<?php if ($is_two_columns):?>
    <div class="drts-frontendsubmit-login-register-form-separator">
        <div class="drts-frontendsubmit-login-register-form-separator-line"></div>
        <div class="drts-frontendsubmit-login-register-form-separator-word">
            <span><?php echo $this->H(__('or', 'directories-frontend'));?></span>
        </div>
    </div>
    <div class="drts-frontendsubmit-login-register-form-column">
<?php   if (isset($html['register'])):?>
        <div class="drts-frontendsubmit-register-form">
            <h2><?php echo $this->H(__('Register', 'directories-frontend'));?></h2>
            <?php echo $html['register'];?>
        </div>
<?php   endif;?>
<?php   if (isset($html['guest'])):?>
        <div class="drts-frontendsubmit-guest-form">
            <h2><?php echo $this->H(__('Continue as guest', 'directories-frontend'));?></h2>
            <?php echo $html['guest'];?>
        </div>
<?php   endif;?>
    </div>
<?php endif;?>
<?php echo $hidden_html;?>
</form>
<?php if ($is_two_columns):?>
<script type="text/javascript">
<?php   echo $js_html;?>
<?php if (\SabaiApps\Directories\Request::isXhr()):?>
jQuery(function($) {
<?php else:?>
document.addEventListener('DOMContentLoaded', function(event) {
<?php endif;?>
    setTimeout(function () {
        var form = jQuery('#<?php echo $form->settings['#id'];?>');
        form.find('.drts-frontendsubmit-login-register-form-separator').css('height', form.outerHeight() + 'px');
    }, 500); // wait for recaptcha to load
});
</script>
<?php endif;?>
