<?php $this->beginContent('application.modules.main.views.layouts.main'); ?>
<script>
	var URL_LOG = '<?php echo $this->createUrl("/main/log/log/writeJs"); ?>';
</script>
<div id="mainContainer">
    <div id="mainContainer2">
        <div id="header">
			<div id="logo"></div>
            <div id="mainMenu">
                <ul>
                    <li><a href="">Images</a></li>
                    <li><a href="">Texts</a></li>
                </ul>
            </div>
        </div>
        <div id="main">
        <?php echo $content; ?>
        </div>
    </div>
</div>
<div id="footer">
    Copyright © <?php echo date('Y') ?>.  all rights reserved.
</div>
<?php $this->endContent(); ?>
