
<?php if ( empty( $this->messages['redirect'] ) ) :
    ?>
<div class="tve-about-us-content-wrapper">
    <div class="tvd-header" id="tve-dash-gt-header">
        <nav id="tvd-nav">
            <div class="nav-wrapper">
                <div class="tve-logo">
                    <a href="<?php echo admin_url( 'admin.php?page=tve_dash_section' ); ?>" class="tvd-users-dashboard-logo" title="Thrive Dashboard">
                        <span class="tvd-logo-container">
                            <img class="thrive_admin_logo" src="<?php echo TVE_DASH_URL ?>/css/images/thrive-logo.png" alt="Thrive Themes Logo">
                        </span>
                    </a>
                </div>
            </div>
        </nav>

        <div class="tve-about-us-content">
            <div class="about-us">
                <p>Hello and welcome to Thrive Themes, the most advanced yet simple-to-use suite of WordPress tools. We create conversion-focused software that helps you build a better online business.</p>
                <p>Our mission is to provide you with everything you need to build a high-converting business <span>on your own,</span> one that turns visitors into subscribers, customers and raving fans.</p>
                <p>Thrive Themes is brought to you by the same team behind the largest WordPress resource site,
                    <a href="https://www.wpbeginner.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content=<?php echo TVE_DASH_VERSION;?>" target="_blank">WP Beginner</a>, the best WordPress analytics plugin,
                    <a href="https://www.monsterinsights.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content=<?php echo TVE_DASH_VERSION;?>" target="_blank">MonsterInsights</a> and more!</p>
                <p>Check out below other great tools that we recommend to help your online business grow even faster:</p>
            </div>

            <div class="the-tve-team">
                <img class="image-behind-the-team" src="<?php echo TVE_DASH_URL ?>/css/images/team-behind-thrive-themes.png" alt="Team Behind Thrive Themes">
                <span class="image-info">Part of the team behind Thrive Themes</span>
            </div>
        </div>
    </div>

    <div class="gt-page-header">
        <div class="gt-page-header-left">
            <span class="tvd-header-title">Integrations and connected apps</span>
            <span class="tvd-header-summary">Recommended products that help you grow your business further</span>
        </div>
        <div class="tvd-filter">
            <div class="tvd-filter-tools"></div>

            <div class="tvd-filter-search">
                <div class="tvd-search-elem"></div>
            </div>
        </div>
    </div>
	<div class="growth-tools-list"></div>
</div>
<?php endif ?>
