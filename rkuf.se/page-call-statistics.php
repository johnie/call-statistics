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

        <?php if (isset($_GET["stats"]) && $_GET["stats"] == "1" && is_super_admin()): ?>
            <?php
                global $call_statistics;
                echo $call_statistics->getStatsHTML();
            ?>
        <?php else: ?>
        <form id="add-call" action="" method="post"><fieldset>
            <!-- personal id -->
            <div>
                <label for="call-personal-id">Fyll i din personliga identifieringskod: <span class="required">*</span></label>
                <input id="call-personal-id" type="text" name="personal_id" value="<?php print isset($_POST["personal_id"]) ? $_POST["personal_id"] : ""; ?>">
            </div>

            <!-- platform -->
            <div>
                <label for="call-platform">Plattform: <span class="required">*</span></label>
                <select id="call-platform" name="platform">
                <?php foreach (get_option("call-statistics_platform_options") as $option): ?>
                    <option value="<?php print $option; ?>" <?php selected(isset($_POST["platform"]) ? $_POST["platform"] : "", $option, TRUE); ?>><?php print $option; ?></option>
                <?php endforeach; ?>
                </select>
            </div>

            <!-- type -->
            <div>
                <label for="call-type">Typ av samtal: <span class="required">*</span></label>
                <select id="call-type" name="type">
                <?php foreach (get_option("call-statistics_type_options") as $option): ?>
                    <option value="<?php print $option; ?>" <?php selected(isset($_POST["type"]) ? $_POST["type"] : "", $option, TRUE); ?>><?php print $option; ?></option>
                <?php endforeach; ?>
                </select>
            </div>

            <!-- minutes -->
            <div>
                <label for="call-minutes">Samtalstid i minuter:</label>
                <input id="call-minutes" name="minutes" type="text" value="<?php print isset($_POST["minutes"]) ? $_POST["minutes"] : ""; ?>">
            </div>

            <!-- gender -->
            <div>
                <label for="call-gender">Kön:</label>
                <select id="call-gender" name="gender">
                <?php foreach (get_option("call-statistics_gender_options") as $option): ?>
                    <option value="<?php print $option; ?>" <?php selected(isset($_POST["gender"]) ? $_POST["gender"] : "", $option, TRUE); ?>><?php print $option; ?></option>
                <?php endforeach; ?>
                </select>
            </div>

            <!-- spouse -->
            <div>
                <label for="call-spouse">Åldersgrupp:</label>
                <select id="call-spouse" name="spouse">
                <?php foreach (get_option("call-statistics_spouse_options") as $option): ?>
                    <option value="<?php print $option; ?>" <?php selected(isset($_POST["spouse"]) ? $_POST["spouse"] : "", $option, TRUE); ?>><?php print $option; ?></option>
                <?php endforeach; ?>
                </select>
            </div>

            <!-- topic -->
            <div>
                <span>Samtalsämne:</span>
                <?php foreach (get_option("call-statistics_topic_options") as $index => $option): ?>
                <label for="call-topic-<?php print $index; ?>">
                    <input id="call-topic-<?php print $index; ?>" type="checkbox" name="topic[]" value="<?php print $option; ?>" <?php if (isset($_POST["topic"]) && in_array($option, $_POST["topic"])): ?>checked="checked"<?php endif; ?>> <?php print $option; ?>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- other category -->
            <div>
                <label for="call-other-category">Annan samtalskategori:</label>
                <div><textarea id="call-other-category" name="other_category"><?php if (isset($_POST["other_category"])) print $_POST["other_category"]; ?></textarea></div>
            </div>

            <!-- reference -->
            <div>
                <label for="call-reference">Hänvisning:</label>
                <input id="call-reference" name="reference" type="text" value="<?php print isset($_POST["reference"]) ? $_POST["reference"] : ""; ?>">
            </div>

            <!-- report -->
            <div>
                <label for="call-report">Rapport om vane- eller jourmissbrukare:</label>
                <div><textarea id="call-report" name="report"><?php if (isset($_POST["report"])) print $_POST["report"]; ?></textarea></div>
            </div>

            <!-- response -->
            <div>
                <label for="call-response">Hur bemötte du vane-eller jourmissbrukaren?</label>
                <div><textarea id="call-response" name="response"><?php if (isset($_POST["response"])) print $_POST["response"]; ?></textarea></div>
            </div>

            <input name="call_statistics_post" type="hidden" value="1">

            <input type="submit" value="Submit" class="btn btn-primary">
        </fieldset></form>
        <?php endif; ?>
        <!-- // end of call statistics -->

	</div>

	<div id="col-sub">
		<?php $rkuf->load_modules(); ?>
	</div>
</div>
<?php get_footer(); ?>
