<?php
if (!defined('ABSPATH')) exit;
?>

<form id="ths-form" data-name-too-long="<?= esc_attr(ThsI18n::lookup('errors', 'name_too_long', 'Tên quá dài.')) ?>" novalidate>
    <div class="ths-form-row">
        <div class="ths-form-group">
            <label for="ths-name"><?= ThsI18n::lookup('form', 'name_label') ?></label>
            <input type="text" id="ths-name" class="ths-input" placeholder="<?= esc_attr(ThsI18n::lookup('form', 'name_placeholder')) ?>" autocomplete="on">
            <span class="ths-error" id="ths-error-name"></span>
        </div>
        <div class="ths-form-group">
            <label for="ths-dob"><?= ThsI18n::lookup('form', 'dob_label') ?></label>
            <input type="text" id="ths-dob" class="ths-input" placeholder="<?= esc_attr(ThsI18n::lookup('form', 'dob_placeholder')) ?>">
            <span class="ths-error" id="ths-error-dob"></span>
        </div>
    </div>
    <button type="submit" class="ths-btn-submit" id="ths-submit-btn">
        <span class="ths-btn-text"><?= ThsI18n::lookup('form', 'btn_analyze') ?></span>
        <span class="ths-btn-loading">
            <span class="ths-spinner"></span> <?= ThsI18n::lookup('form', 'loading_analyzing') ?>
        </span>
    </button>
</form>
