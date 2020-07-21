<!doctype html>
<html>
	<head>
		<?=ee()->view->head_title($cp_page_title)?>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<?php if (isset($meta_refresh)): ?>
		<meta http-equiv='refresh' content='<?=$meta_refresh['rate']?>; url=<?=$meta_refresh['url']?>'>
		<?php endif;?>

		<?=ee()->view->head_link('css/common.min.css'); ?>
		<?php if (ee()->extensions->active_hook('cp_css_end') === TRUE):?>
		<link rel="stylesheet" href="<?=ee('CP/URL', 'css/cp_global_ext')?>" type="text/css" />
		<?php endif;?>

		<?php
		foreach (ee()->cp->get_head() as $item) {
			echo $item."\n";
		}
		?>
	</head>
	<body data-ee-version="<?=APP_VER?>" id="top">
		<script>
		var currentTheme = localStorage.getItem('theme');

		// Restore the currently selected theme
		// This is at the top of the body to prevent the default theme from flashing
		if (currentTheme) {
			document.body.dataset.theme = currentTheme;
		}
		</script>

		<div class="global-alerts">
		<?=ee('CP/Alert')->getAllBanners()?>
		</div>

		<div class="theme-switch-circle"></div>

<?php
// Get the current page to highlight it in the sidebar
$current_page = ee()->uri->segment(2);
?>

	<div class="ee-wrapper-overflow">
		<section class="ee-wrapper">
			<?php if (!isset($hide_sidebar) || $hide_sidebar!=true) :
			echo ee('CP/NavigationSidebar')->render();
			endif; ?>
			<div class="ee-main">

        <div class="ee-main-header">

          <a href="" class="sidebar-toggle" title="Toggle Sidebar"><i class="fas fa-angle-left"></i></a>

          <a class="main-nav__mobile-menu js-toggle-main-sidebar hidden">
        		<svg xmlns="http://www.w3.org/2000/svg" width="18.585" height="13.939" viewBox="0 0 18.585 13.939"><g transform="translate(-210.99 -17.71)"><path d="M3,12.1H19.585" transform="translate(208.99 12.575)" fill="none" stroke-linecap="round" stroke-width="2"/><path d="M3,6H19.585" transform="translate(208.99 12.71)" fill="none" stroke-linecap="round" stroke-width="2"/><path d="M3,18H9.386" transform="translate(208.99 12.649)" fill="none" stroke-linecap="round" stroke-width="2"/></g></svg>
        	</a>

          <?php if (count($cp_breadcrumbs)): ?>
            <div class="breadcrumb-wrapper">
              <ul class="breadcrumb">
        				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
        					<li><a href="<?=$link?>"><?=$title?></a></li>
        				<?php endforeach ?>
        			</ul>
            </div>
      		<?php endif ?>

          <div class="field-control field-control_input--jump with-icon-start with-input-shortcut">
            <i class="fas fa-bullseye fa-fw icon-start"></i>
            <input type="text" class="input--jump input--rounded" placeholder="Jump to...">
            <span class="input-shortcut">⌘J</span>
          </div>

          <div class="main-header__account">
            <button type="button" data-dropdown-offset="0px, 4px" data-dropdown-pos="bottom-end" class="main-nav__account-icon main-header__account-icon js-dropdown-toggle">
      				<img src="<?= $cp_avatar_path ?>" alt="<?=$cp_screen_name?>">
      			</button>
            <div class="dropdown dropdown--accent account-menu">
      				<div class="account-menu__header">
      					<div class="account-menu__header-title">
      						<h2><?=$cp_screen_name?></h2>
      						<span><?=$cp_member_primary_role_title?></span>
      					</div>

      				</div>

      				<a class="dropdown__link" href="<?=ee('CP/URL')->make('members/profile', array('id' => ee()->session->userdata('member_id')))?>"><i class="fas fa-user fa-fw"></i> <?=lang('my_profile')?></a>
      				<a class="dropdown__link" href="<?=ee('CP/URL', 'login/logout')?>"><i class="fas fa-sign-out-alt fa-fw"></i> <?=lang('log_out')?></a>

      				<div class="dropdown__divider"></div>

      				<a class="dropdown__link js-jump-menu-trigger" href=""><i class="fas fa-bullseye fa-fw"></i> <?= lang('jump_menu_item') ?> <span class="dropdown__link-shortcut"><span class="jump-trigger"></span>J</span></a>
      				<a class="dropdown__link js-dark-theme-toggle" href=""><i class="fas fa-adjust fa-fw"></i> <?= lang('dark_theme') ?></a>

      				<div class="dropdown__divider"></div>

      				<h3 class="dropdown__header"><?=lang('quick_links')?></h3>
      				<?php foreach($cp_quicklinks as $link): ?>
      				<a class="dropdown__link" href="<?=$link['link']?>"><?=htmlentities($link['title'], ENT_QUOTES, 'UTF-8')?></a>
      				<?php endforeach ?>
      				<a class="dropdown__link" href="<?=ee('CP/URL')->make('members/profile/quicklinks/create', array('id' => ee()->session->userdata('member_id'), 'url' => ee('CP/URL')->getCurrentUrl()->encode(), 'name' => $cp_page_title))?>"><i class="fas fa-plus fa-sm"></i>  <?=lang('new_link')?></a>
      			</div>
          </div>


        </div>

<?php
