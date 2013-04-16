<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */

get_header(); ?>

<div id="primary" class="site-content">
    <div id="content" role="main">

        <?php while ( have_posts() ) : the_post(); ?>
            <?php get_template_part( 'content', 'page' ); ?>
            <?php comments_template( '', true ); ?>
        <?php endwhile; // end of the loop. ?>

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

    </div><!-- #content -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
