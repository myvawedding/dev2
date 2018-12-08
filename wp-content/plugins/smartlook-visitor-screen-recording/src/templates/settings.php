<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,700&amp;subset=latin,latin-ext" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="<?= $base ?>/assets/bootstrap.min.css" />
<link rel="stylesheet" href="<?= $base ?>/assets/smartlook.css">

<div id="content">
	<div class="wrap">
		<?php if ($enabled) { ?>
			<main role="main">
				<div id="third">
					<div class="main-bg">
						<section class="top-bar">
							<div class="text-center">
							</div>
						</section>
						<section>
							<div class="row">
								<p class="text-gray">
									<span class="left">
										<img src="<?= $base ?>/img/logo.png" alt="smartlook logo" />
									</span>
									<span class="right">
										<br>
										<?= isset($email) ? $email : '' ?> | <a class="js-action-disable" href="javascript: void(0);"><?= __('Disconnect account', $domain) ?></a>
									</span>
								</p>
								<div class="clear"></div>
								<section class="intro">
									<h1 class="lead"><?= __('Have you got your popcorn ready?', $domain) ?></h1>
									<h4 class="text-gray"><?= __('Go to Smartlook to see and filter visitor recordings.', $domain) ?></h4>
									<?php if ($project): ?>
										<div class="intro--btn">
											<a href="https://www.smartlook.com/app/dashboard?utm_source=Wordpress&utm_medium=integration&utm_campaign=link" target="_blank" class="js-register btn btn-primary btn-xl">
												<?= __('Go to Smartlook', $domain) ?>
											</a>
										</div>
										<p class="tiny text-center text-gray bigger-m"><?= __('(This will open a new browser tab)', $domain) ?></p>
									<?php endif; ?>

									<div class="spacer"></div>
									<div class="spacer"></div>

									<?php if ($displayForm): ?>
										<form class="form-centered js-project-form">
											<div class="row">
												<div class="col-sm-4 text-right">
													<label for="project"><?= __('Choose project', $domain) ?></label>
												</div>
												<div class="col-sm-4">
													<select id="project" name="project" class="form-control js-project-select">
														<option value=""><?= __('Create new project', $domain) ?></option>
														<?php $selected = FALSE; ?>
														<?php foreach ($projects as $p): ?>
															<option<?php if ($project && $project == $p['id']): ?> selected<?php $selected = TRUE; endif; ?> value="<?= $p['id'] ?>"><?= $p['name'] ?></option>
														<?php endforeach; ?>
													</select>
												</div>
												<div class="col-sm-4"></div>
											</div>
											<div class="row js-new-project"<?php if ($selected): ?> style="display: none;"<?php endif; ?>>
												<div class="col-sm-4 text-right">
													<label for="projectName"><?= __('Name for new project', $domain) ?></label>
												</div>
												<div class="col-sm-4">
													<input type="text" id="projectName" name="projectName" class="form-control">
												</div>
												<div class="col-sm-4"></div>
											</div>
											<div class="row">
												<div class="col-sm-4"></div>
												<div class="col-sm-4">
													<button type="submit" class="btn btn-default"><?= __('Assign project to this web', $domain) ?></button>
												</div>
												<div class="col-sm-4"></div>
											</div>
											<div class="loader"></div>
										</form>
									<?php endif; ?>
								</section>
							</div>
						</section>
					</div>
				</div>
			</main>
		<?php } else { ?>
			<main role="main" class="sections" id="home"<?php if ($formAction) { ?> style="display: none;"<?php } ?>>
				<div id="first">
					<div class="main-bg with-guy">
						<section class="top-bar">
							<img src="<?= $base ?>/img/logo.png" alt="smartlook logo" />
							<a href="javascript: void(0);" class="js-login btn btn-default"><?= __('Connect existing account', $domain) ?></a>
						</section>
						<section class="intro">
							<div>
								<h1 class="lead">
									<?= __('We will record everything visitors do.<br>On every website. For free.', $domain) ?>
								</h1>
								<h3><?= __('Look at your website through your customer\'s eyes!', $domain) ?></h3>

								<div class="alerts row">
									<?php if ($message) { ?>
										<div class="col-sm-4"></div>
										<div class="col-sm-4">
											<div class="alert alert-danger js-clear">
												<?= __($message, $domain) ?>
											</div>
										</div>
										<div class="col-sm-4"></div>
									<?php } ?>
								</div>

								<form class="form-centered js-register-form">
									<div class="row">
										<div class="col-sm-4 text-right">
											<label for="email"><?= __('Email', $domain) ?></label>
										</div>
										<div class="col-sm-4">
											<input id="email" type="email" name="email" class="form-control" required="" value="<?= isset($email) ? $email : '' ?>">
										</div>
										<div class="col-sm-4"></div>
									</div>
									<div class="row">
										<div class="col-sm-4 text-right">
											<label for="password"><?= __('Password', $domain) ?></label>
										</div>
										<div class="col-sm-4">
											<input id="password" type="password" name="password" class="form-control" required="">
										</div>
										<div class="col-sm-4"></div>
									</div>
                                    <div class="row gdpr checkbox">
                                        <div class="col-sm-4"></div>
                                        <div class="col-sm-4">
                                            <label for="frm-signUp-form-termsConsent">
                                                <input <?= !empty($termsConsent) ? 'checked="checked"' : '' ?> value="1" type="checkbox" name="termsConsent" id="frm-signUp-form-termsConsent" required="">&nbsp;<?= __('I have read and agree with <a href="https://www.smartlook.com/terms" target="_blank">Terms</a>', $domain); ?>
                                            </label>
                                        </div>
                                        <div class="col-sm-4"></div>
                                    </div>
									<div class="row">
										<div class="col-sm-4"></div>
										<div class="col-sm-4">
											<input type="submit" class="btn btn-primary btn-xl" value="<?= __('Create new account', $domain) ?>">
										</div>
										<div class="col-sm-4"></div>
									</div>
									<div class="loader"></div>
								</form>
							</div>
						</section>
					</div>
					<section>
						<div class="text-center">
							<div class="section--header">
								<h2><?= __('We\'ll record all visitor movement on your website', $domain) ?></h2>
							</div>
							<div class="section--body">
								<div class="row big-gray">
									<?= __('Smartlook is a simple tool which records the screens of real users on your website.<br>You can see what visitors clicked with their mouse, what they filled into a form field,<br>where they spend most of their time, and how they browse through each page.', $domain) ?>
								</div>
								<div class="row big-gray">
									<?= __('See all features on <a target="_blank" href="https://www.smartlook.com/?utm_source=Wordpress&utm_medium=integration&utm_campaign=link">our website</a>.', $domain) ?>
								</div>
							</div>
							<div>
								<img src="<?= $base ?>/img/screen.png">
							</div>
							<div class="section--header">
								<h2><?= __('More than 80 000 happy customers', $domain) ?></h2>
							</div>
							<div class="section--body">
								<div class="customers">
									<img src="<?= $base ?>/img/logo-hyundai.png" alt="Hyundai" />
									<img src="<?= $base ?>/img/logo-kiwi.png" alt="Kiwi.com" />
									<img src="<?= $base ?>/img/logo-conrad.png" alt="Conrad" />
								</div>
							</div>
						</div>
					</section>
				</div>
			</main>
			<main role="main" class="sections" id="connect"<?php if (!$formAction) { ?> style="display: none;"<?php } ?>>
				<div id="second">
					<div class="main-bg">
						<section class="top-bar">
							<div>
								<a href="javascript: void(0);" class="js-close-form">
									<img src="<?= $base ?>/img/logo.png" alt="smartlook logo" />
									<a href="javascript: void(0);" class="js-close-form btn btn-default"><?= __('Create free account', $domain) ?></a>
								</a>
							</div>
						</section>
						<section id="signUp">
							<div class="text-center">
								<div>
									<div class="section--header">
										<h1 class="lead">
											<?= __('Connect existing account', $domain) ?>
										</h1>
									</div>
									<div class="section--body">
										<div class="form--inner">
											<div class="alerts row">
												<?php if ($message) { ?>
													<div class="col-sm-4"></div>
													<div class="col-sm-4">
														<div class="alert alert-danger js-clear">
															<?= __($message, $domain) ?>
														</div>
													</div>
													<div class="col-sm-4"></div>
												<?php } ?>
											</div>
											<form class="form-centered js-login-form">
												<div class="row">
													<div class="col-sm-4 text-right">
														<label for="email"><?= __('Email', $domain) ?></label>
													</div>
													<div class="col-sm-4">
														<input id="email" type="email" name="email" class="form-control" required="" value="<?= isset($email) ? $email : '' ?>">
													</div>
													<div class="col-sm-4"></div>
												</div>
												<div class="row">
													<div class="col-sm-4 text-right">
														<label for="password"><?= __('Password', $domain) ?></label>
													</div>
													<div class="col-sm-4">
														<input id="password" type="password" name="password" class="form-control" required="">
													</div>
													<div class="col-sm-4"></div>
												</div>
												<div class="row">
													<div class="col-sm-4"></div>
													<div class="col-sm-4">
														<input type="submit" class="btn btn-primary btn-xl" value="<?= __('Connect existing account', $domain) ?>">
													</div>
													<div class="col-sm-4"></div>
												</div>
												<div class="spaced-row">
													<a target="_blank" href="https://www.smartlook.com/sign/password-reset?utm_source=Wordpress&utm_medium=integration&utm_campaign=link"><?= __('I forgot my password', $domain) ?></a>
												</div>
												<div class="loader"></div>
											</form>
										</div>
									</div>
								</div>
							</div>
						</section>
					</div>
				</div>
			</main>
		<?php } ?>
	</div>
</div>

<script src="<?= $base ?>/assets/smartlook.js"></script>
