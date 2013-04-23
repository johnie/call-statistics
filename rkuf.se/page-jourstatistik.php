<?php get_header(); ?>
<div id="content" class="type-b">
	<div id="col-sup">
		<?php if($rkuf->has_submenu("back=1")): ?>
			<ul id="nav-sub">
				<?php $rkuf->the_submenu("back=1"); ?>
			</ul>
		<?php endif; ?>
		&nbsp;
	</div>
	<div id="col-main">
		<?php the_post(); ?>
		<div class="document">
			<?php the_content(); ?>
		</div>
		<?php do_action("rkuf_content"); ?>

        <!-- start of call statistics -->

        <!-- message -->
        <?php if (class_exists("Call_Stats")): ?>
            <?php foreach (Call_Stats::getMessages() as $level => $messages): ?>
                <?php foreach ($messages as $message): ?>
                <div class="alert alert-<?php print $level; ?>"><?php print $message; ?></div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- call stats content -->
        <?php global $call_statistics; ?>
        <?php if ($call_statistics): ?>
            <?php if (isset($_GET["stats"]) && $_GET["stats"] == "1" && is_super_admin()): ?>
                <?php echo $call_statistics->view->getStatsHTML(); ?>
            <?php elseif (isset($_GET["list"]) && $_GET["list"] == "1" && is_super_admin()): ?>
                <?php echo $call_statistics->view->getListHTML(); ?>
            <?php else: ?>
                <?php echo $call_statistics->view->getFormHTML(); ?>
            <?php endif; ?>
        <?php endif; ?>
        <!-- // end of call statistics -->

	</div>

<!--	<div id="col-sub">
		<?php $rkuf->load_modules(); ?>
	</div>
-->
</div>
<?php get_footer(); ?>
