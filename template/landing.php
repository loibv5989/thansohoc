<?php
if (!defined('ABSPATH')) exit;
?>

<div class="ths-page ths-landing" id="ths-landing-wrapper">
    <section class="ths-hero">
        <div class="ths-hero-svg-wrap ths-toggle-content">
            <svg width="100%" viewBox="0 0 680 400" preserveAspectRatio="xMidYMid slice">
                <use href="#svg-hero-illu"></use>
            </svg>
        </div>
        <div class="ths-hero-content">
            <div class="ths-hero-copy ths-toggle-content">
                <div class="ths-hero-badge"><?php ThsI18n::e(ThsI18n::lookup('landing', 'hero_badge')) ?></div>
                <h1 class="ths-hero-title"><span><?php ThsI18n::e(ThsI18n::lookup('landing', 'hero_title')) ?></span></h1>
                <p class="section-desc"><?= ThsI18n::t(ThsI18n::lookup('landing', 'hero_desc')) ?></p>
            </div>
        </div>
    </section>

    <section class="ths-hero-form-wrap" id="ths-form-section">
        <div class="ths-calc-card">
            <?php require THAN_SO_HOC_PLUGIN_DIR . 'template/form.php'; ?>
        </div>
        <div class="ths-result" id="ths-result"></div>
        <p class="section-note"><span><?php ThsI18n::e(ThsI18n::lookup('landing', 'method_label')) ?></span>: <?php ThsI18n::e(ThsI18n::lookup('landing', 'method_note')) ?></p>
    </section>

    <section class="lp-section ths-toggle-content">
        <div class="lp-container">
            <h2 class="section-title"><?php ThsI18n::e(ThsI18n::lookup('landing', 'essence_title')) ?></h2>
            <p class="section-desc"><?= ThsI18n::t(ThsI18n::lookup('landing', 'essence_desc')) ?></p>
            <div class="intro-grid">
                <div class="intro-card">
                    <h3 class="intro-card-title"><?php ThsI18n::e(ThsI18n::lookup('landing', 'intro_card_1_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'intro_card_1_desc')) ?></p>
                </div>

                <div class="intro-card">
                    <h3 class="intro-card-title"><?php ThsI18n::e(ThsI18n::lookup('landing', 'intro_card_2_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'intro_card_2_desc')) ?></p>
                </div>

                <div class="ths-sv-sc">
                    <div class="ths-sv-card ths-sv-card1">
                        <div class="ths-sv-icon">▲</div>
                        <div class="ths-sv-title"><?php ThsI18n::e(ThsI18n::lookup('landing', 'life_path')) ?></div>
                        <div class="ths-sv-desc"><?php ThsI18n::e(ThsI18n::lookup('landing', 'life_path_desc')) ?></div>
                    </div>
                    <div class="ths-sv-card ths-sv-card2">
                        <div class="ths-sv-icon">❤</div>
                        <div class="ths-sv-title"><?php ThsI18n::e(ThsI18n::lookup('landing', 'soul_urge')) ?></div>
                        <div class="ths-sv-desc"><?php ThsI18n::e(ThsI18n::lookup('landing', 'soul_urge_desc')) ?></div>
                    </div>
                    <div class="ths-sv-card ths-sv-card3">
                        <div class="ths-sv-icon">⌖</div>
                        <div class="ths-sv-title"><?php ThsI18n::e(ThsI18n::lookup('landing', 'expression')) ?></div>
                        <div class="ths-sv-desc"><?php ThsI18n::e(ThsI18n::lookup('landing', 'expression_desc')) ?></div>
                    </div>
                    <div class="ths-sv-card ths-sv-card4">
                        <div class="ths-sv-icon">☺</div>
                        <div class="ths-sv-title"><?php ThsI18n::e(ThsI18n::lookup('landing', 'personality')) ?></div>
                        <div class="ths-sv-desc"><?php ThsI18n::e(ThsI18n::lookup('landing', 'personality_desc')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lp-section ths-toggle-content" id="lp-mean-section">
        <div class="lp-container">
            <div class="section-label"><?php ThsI18n::e(ThsI18n::lookup('landing', 'meaning_label')) ?></div>
            <h2 class="section-title"><?php ThsI18n::e(ThsI18n::lookup('landing', 'meaning_title')) ?></h2>
            <p class="section-desc"><?= ThsI18n::t(ThsI18n::lookup('landing', 'meaning_desc')) ?></p>

            <div class="ths-meaning-nav">
                <?php foreach([1,2,3,4,5,6,7,8,9,11,22] as $n): ?>
                    <button class="ths-num-tab <?php echo $n === 1 ? 'active' : ''; ?>" onclick="thsSwitchNum(<?php echo $n; ?>, this)">
                        <?php echo $n; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="ths-meaning-contents">
                <div class="ths-num-content active" id="ths-mean-1">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_1_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_1_desc')) ?></p>
                </div>
                <div class="ths-num-content" id="ths-mean-2">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_2_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_2_desc')) ?></p>
                </div>
                <div class="ths-num-content" id="ths-mean-3">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_3_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_3_desc')) ?></p>
                </div>
                <div class="ths-num-content" id="ths-mean-4">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_4_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_4_desc')) ?></p>
                </div>
                <div class="ths-num-content" id="ths-mean-5">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_5_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_5_desc')) ?></p>
                </div>
                <div class="ths-num-content" id="ths-mean-6">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_6_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_6_desc')) ?></p>
                </div>
                <div class="ths-num-content" id="ths-mean-7">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_7_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_7_desc')) ?></p>
                </div>
                <div class="ths-num-content" id="ths-mean-8">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_8_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_8_desc')) ?></p>
                </div>
                <div class="ths-num-content" id="ths-mean-9">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_9_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_9_desc')) ?></p>
                </div>
                <div class="ths-num-content" id="ths-mean-11">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_11_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_11_desc')) ?></p>
                </div>
                <div class="ths-num-content" id="ths-mean-22">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'lp_22_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'lp_22_desc')) ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="lp-section ths-toggle-content" id="ths-theory-section">
        <div class="lp-container">
            <h2 class="section-title"><?php ThsI18n::e(ThsI18n::lookup('landing', 'theory_title')) ?></h2>
            <div class="theory-grid">
                <div class="theory-item">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'theory_1_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'theory_1_desc')) ?></p>
                </div>
                <div class="theory-item">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'theory_2_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'theory_2_desc')) ?></p>
                </div>
                <div class="theory-item">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'theory_3_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'theory_3_desc')) ?></p>
                </div>
                <div class="theory-item">
                    <h3><?php ThsI18n::e(ThsI18n::lookup('landing', 'theory_4_title')) ?></h3>
                    <p><?= ThsI18n::t(ThsI18n::lookup('landing', 'theory_4_desc')) ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="lp-section ths-toggle-content">
        <div class="lp-container">
            <h3 class="section-title"><?php ThsI18n::e(ThsI18n::lookup('landing', 'faq_title')) ?></h3>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-q">
                        <span><?php ThsI18n::e(ThsI18n::lookup('landing', 'faq_q1')) ?></span>
                        <span class="faq-chevron">▼</span>
                    </div>
                    <div class="faq-a"><?= ThsI18n::t(ThsI18n::lookup('landing', 'faq_a1')) ?></div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">
                        <span><?php ThsI18n::e(ThsI18n::lookup('landing', 'faq_q2')) ?></span>
                        <span class="faq-chevron">▼</span>
                    </div>
                    <div class="faq-a"><?= ThsI18n::t(ThsI18n::lookup('landing', 'faq_a2')) ?></div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">
                        <span><?php ThsI18n::e(ThsI18n::lookup('landing', 'faq_q3')) ?></span>
                        <span class="faq-chevron">▼</span>
                    </div>
                    <div class="faq-a"><?= ThsI18n::t(ThsI18n::lookup('landing', 'faq_a3')) ?></div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">
                        <span><?php ThsI18n::e(ThsI18n::lookup('landing', 'faq_q4')) ?></span>
                        <span class="faq-chevron">▼</span>
                    </div>
                    <div class="faq-a"><?= ThsI18n::t(ThsI18n::lookup('landing', 'faq_a4')) ?></div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">
                        <span><?php ThsI18n::e(ThsI18n::lookup('landing', 'faq_q5')) ?></span>
                        <span class="faq-chevron">▼</span>
                    </div>
                    <div class="faq-a"><?= ThsI18n::t(ThsI18n::lookup('landing', 'faq_a5')) ?></div>
                </div>
            </div>
        </div>
    </section>
</div>
