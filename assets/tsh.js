jQuery(function ($) {
    const NAME_MAX_LENGTH = 80;
    let analysisLoadingTexts = null;

    const DATE_INPUT_RULES = {
        default: {order: ['day', 'month', 'year'], separator: '/'},
        en: {order: ['month', 'day', 'year'], separator: '/'},
        de: {order: ['day', 'month', 'year'], separator: '.'},
        ja: {order: ['year', 'month', 'day'], separator: '/'},
        ko: {order: ['year', 'month', 'day'], separator: '.'},
        zh: {order: ['year', 'month', 'day'], separator: '-'}
    };

    const UI = {
        $form: $('#ths-form'),
        $resultBox: $('#ths-result'),
        $submitBtn: $('#ths-submit-btn')
    };

    const Messages = {
        nameTooLong() {
            return UI.$form.attr('data-name-too-long') || 'Tên quá dài.';
        }
    };

    const Loading = {
        set(isActive) {
            if (isActive) {
                UI.$submitBtn.addClass('ths-loading').attr('disabled', true);
            } else {
                UI.$submitBtn.removeClass('ths-loading').removeAttr('disabled');
            }
        }
    };

    const getLocale = () => (document.documentElement.lang || '').replace('-', '_');

    const DateValidator = {
        locale() {
            return (document.documentElement.lang || '').toLowerCase();
        },
        languageCode() {
            return this.locale().replace('_', '-').split('-')[0] || 'default';
        },
        rule() {
            return DATE_INPUT_RULES[this.languageCode()] || DATE_INPUT_RULES.default;
        },
        separator() {
            return this.rule().separator;
        },
        order() {
            return this.rule().order;
        },
        formatLabel() {
            const labels = {day: 'DD', month: 'MM', year: 'YYYY'};
            return this.order().map((part) => labels[part]).join(this.separator());
        },
        messages() {
            const dateFormat = this.formatLabel();
            return {
                required: 'Please enter your date of birth.',
                range: 'Supported years are 1900 to 2100.',
                month: `Invalid month. Use ${dateFormat}.`,
                day: `Invalid day. Use ${dateFormat}.`,
                notExists: (day, month, year) => `Date ${this.display(year, month, day)} does not exist.`
            };
        },
        formatError() {
            const examples = {day: '15', month: '8', year: '1990'};
            const format = this.formatLabel();
            const example = this.order().map((part) => examples[part]).join(this.separator());
            return `Format: ${format} (e.g. ${example})`;
        },
        display(year, month, day) {
            const values = {day, month, year};
            return this.order().map((part) => values[part]).join(this.separator());
        },
        validate(value, allowEmpty = false) {
            const messages = this.messages();
            value = (value || '').trim();
            if (!value) {
                return {valid: allowEmpty, message: allowEmpty ? '' : messages.required};
            }

            let match = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            const parts = {day: 0, month: 0, year: 0};
            if (match) {
                parts.year = parseInt(match[1], 10);
                parts.month = parseInt(match[2], 10);
                parts.day = parseInt(match[3], 10);
            } else {
                const order = this.order();
                match = order[0] === 'year'
                    ? value.match(/^(\d{4})[\/\-.\s](\d{1,2})[\/\-.\s](\d{1,2})$/)
                    : value.match(/^(\d{1,2})[\/\-.\s](\d{1,2})[\/\-.\s](\d{4})$/);
                if (!match) return {valid: false, message: this.formatError()};

                order.forEach((part, index) => {
                    parts[part] = parseInt(match[index + 1], 10);
                });
            }

            const {day, month, year} = parts;
            if (year < 1900 || year > 2100) return {valid: false, message: messages.range};
            if (month < 1 || month > 12) return {valid: false, message: messages.month};
            if (day < 1 || day > 31) return {valid: false, message: messages.day};

            const testDate = new Date(year, month - 1, day);
            if (testDate.getFullYear() !== year || testDate.getMonth() !== month - 1 || testDate.getDate() !== day) {
                return {valid: false, message: messages.notExists(day, month, year)};
            }

            const iso = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const display = this.display(year, month, day);

            return {valid: true, value: display, iso};
        },
        validateInput(inputId, errorId, allowEmpty = false) {
            const $input = $('#' + inputId);
            const $error = $('#' + errorId);
            const result = this.validate($input.val(), allowEmpty);
            $input.removeClass('is-error');
            $error.text('');
            if (!result.valid) {
                $error.text(result.message);
                $input.addClass('is-error');
                return false;
            }
            if (result.value) $input.val(result.value);
            return result;
        },
        bind(inputId, errorId) {
            const $input = $('#' + inputId);
            if (!$input.length) return;
            $input.attr('maxlength', 10);
            $input.on('input', function () {
                let value = $(this).val().replace(/[^\d\/\-.\s]/g, '');
                if (value.length > 10) value = value.substring(0, 10);
                $(this).val(value).removeClass('is-error');
                $('#' + errorId).text('');
            });
            $input.on('blur', () => {
                if (!$input.val().trim()) return;
                this.validateInput(inputId, errorId, true);
            });
        }
    };

    const Validator = {
        normalizeName(value) {
            const cleaned = (value || '').replace(/[^\p{L}\p{M}\s'’.-]/gu, '').replace(/\s+/g, ' ').trim();
            if (!cleaned) return '';
            const locale = document.documentElement.lang || undefined;
            return cleaned.toLocaleLowerCase(locale).split(' ').filter(Boolean).map((word) => {
                return word.split('-').map((part) => {
                    return part ? (part.charAt(0).toLocaleUpperCase(locale) + part.slice(1)) : '';
                }).join('-');
            }).join(' ');
        },

        name(inputId, errorId) {
            const $input = $('#' + inputId);
            const $error = $('#' + errorId);
            const rawValue = $input.val();
            $input.removeClass('is-error');
            $error.text('');
            const value = this.normalizeName(rawValue);
            $input.val(value);
            if (!value) {
                $error.text('Please enter a valid full name.');
                $input.addClass('is-error');
                return false;
            }
            if (value.length > NAME_MAX_LENGTH) {
                $error.text(Messages.nameTooLong());
                $input.addClass('is-error');
                return false;
            }
            return value;
        },

        optionalName(inputId, errorId) {
            const $input = $('#' + inputId);
            const $error = $('#' + errorId);
            const rawValue = $input.val();
            $input.removeClass('is-error');
            $error.text('');
            const value = this.normalizeName(rawValue);
            $input.val(value);
            if (!value) return '';
            if (value.length > NAME_MAX_LENGTH) {
                $error.text(Messages.nameTooLong());
                $input.addClass('is-error');
                return false;
            }
            return value;
        }
    };

    const Color = {
        map: {
            life_path: '#8b5cf6',
            expression: '#a855f7',
            attitude: '#ec4899',
            birthday: '#f97316',
            soul_urge: '#10b981',
            personality: '#06b6d4',
            maturity: '#84cc16',
            balance: '#14b8a6',
            karmic_lessons: '#ef4444',
            karmic_debt: '#b91c1c',
            pinnacles: '#eab308',
            challenges: '#64748b',
            personal_year: '#f59e0b',
            personal_month: '#0ea5e9',
            personal_day: '#3b82f6'
        },
        forValue(key) {
            return this.map[key] || '#8b5cf6';
        }
    };

    const Typewriter = {
        fastMode: false,
        speedMultiplier: 1,

        renderHint(hintText) {
            if (!hintText) return null;
            if (!hintText.includes('<br>- ')) {
                return $('<span class="ths-tw-hint ths-tw-hint-text"></span>').text(hintText);
            }

            const items = hintText
                .split(/<br>\s*-\s*/i)
                .map((item) => item.replace(/^-\s*/, '').trim())
                .filter(Boolean);

            if (!items.length) return null;

            const $hint = $('<div class="ths-tw-hint ths-tw-hint-text ths-tw-hint-list"></div>');
            const $list = $('<ul></ul>');
            items.forEach((item) => {
                $('<li></li>').text(item).appendTo($list);
            });
            $hint.append($list);
            return $hint;
        },

        typeText($element, text, speed, callback, allowHtml = false) {
            let charIndex = 0;
            const tick = () => {
                if (charIndex >= text.length) {
                    if (callback) callback();
                    return;
                }
                if (allowHtml) {
                    $element.html(text);
                    charIndex = text.length;
                } else {
                    $element.append(document.createTextNode(text[charIndex++]));
                }

                const currentSpeed = (Typewriter.fastMode ? 4 : speed) * Typewriter.speedMultiplier;
                const randomDelay = Typewriter.fastMode ? 0 : (Math.random() * 10) * Typewriter.speedMultiplier;
                setTimeout(tick, currentSpeed + randomDelay);
            };
            tick();
        },

        run(lines, onDoneCallback, isCached = false) {
            Typewriter.fastMode = false;
            Typewriter.speedMultiplier = isCached ? 0.5 : 1;

            const $cursor = $('#ths-chat-body .ths-cursor');
            let lineIndex = 0;
            const processNextLine = () => {
                if (lineIndex >= lines.length) {
                    $cursor.addClass('hidden');
                    $('.ths-tw-closing').remove();
                    if (onDoneCallback) onDoneCallback();
                    return;
                }
                const line = lines[lineIndex++];
                if (line.type === 'divider') {
                    $cursor.before('<div class="ths-tw-divider"></div>');
                    setTimeout(processNextLine, (Typewriter.fastMode ? 6 : 54) * Typewriter.speedMultiplier);
                    return;
                }
                if (line.type === 'index') {
                    const $el = $('<div class="ths-tw-index" data-index-key="' + line.key + '"></div>');
                    $cursor.before($el);
                    const color = Color.forValue(line.key);
                    const full = line.label + ': ';
                    const number = String(line.value);
                    const hintText = line.hint || '';

                    this.typeText($el, full, 10, () => {
                        const $num = $('<span class="ths-tw-num" style="color:' + color + '">' + number + '</span>');
                        $el.append($num);
                        if (hintText) {
                            const $hint = this.renderHint(hintText);
                            if ($hint) {
                                if ($hint.hasClass('ths-tw-hint-list')) {
                                    $el.addClass('has-list-hint');
                                }
                                $el.append($hint);
                            }
                        }
                        setTimeout(processNextLine, (Typewriter.fastMode ? 6 : 36) * Typewriter.speedMultiplier);
                    });
                    return;
                }
                const cssMap = {
                    greeting: 'ths-tw-greeting',
                    intro: 'ths-tw-intro',
                    closing: 'ths-tw-closing',
                    easter: 'ths-tw-easter',
                    block: 'ths-tw-block'
                };
                const cssClass = cssMap[line.type] || 'ths-tw-text';
                const $el = $('<div class="' + cssClass + '"></div>');
                $cursor.before($el);
                const speed = line.type === 'greeting' ? 10 : 10;
                const allowHtml = line.type === 'block' || line.type === 'easter';

                this.typeText($el, line.text, speed, () => setTimeout(processNextLine, (Typewriter.fastMode ? 7 : 67) * Typewriter.speedMultiplier), allowHtml);
            };
            processNextLine();
        }
    };

    const TabHandler = {
        init() {
            $('#ths-result').on('click', '.ths-tab', function (e) {
                e.stopPropagation();
                const tabId = $(this).data('tab');
                const $wrap = $(this).closest('.ths-analysis-wrap');
                $wrap.find('.ths-tab').removeClass('active');
                $(this).addClass('active');
                $wrap.find('.ths-tab-pane').removeClass('active');
                $('#ths-tab-' + tabId).addClass('active');
            });
        }
    };

    const Auth = {
        openLogin() {
            $('.user-dropdown .user-dropdown-item:first').trigger('click');
        }
    };

    const LocaleSession = {
        key: 'ths:locale',
        current() {
            return (document.documentElement.lang || '').replace('-', '_').trim();
        },
        storages() {
            return [sessionStorage, localStorage];
        },
        clearPluginData() {
            this.storages().forEach(storage => {
                try {
                    Object.keys(storage).forEach(key => {
                        if (key.startsWith('ths:')) storage.removeItem(key);
                    });
                } catch (e) {}
            });
            $('#ths-landing-wrapper').removeClass('is-focused');
            $('#ths-result').empty().hide();
            if ($('#ths-form').length) $('#ths-form')[0].reset();
            $('.ths-btn-reset').hide();
        },
        init() {
            const current = this.current();
            if (!current) return;

            let hasDifferentLocale = false;
            for (const storage of this.storages()) {
                try {
                    const previous = storage.getItem(this.key);
                    if (previous && previous !== current) {
                        hasDifferentLocale = true;
                    }
                } catch (e) {}
            }

            if (hasDifferentLocale) {
                this.clearPluginData();
            }

            this.storages().forEach(storage => {
                try {
                    storage.setItem(this.key, current);
                } catch (e) {}
            });
        },
        bindLanguageLinks() {
            $(document).on('click', 'a[data-gt-lang], a[hreflang], .lang-item a, .wpml-ls-link, .pll-parent-menu-item a, .lbv-language a, .lang-switcher-dropdown a', (e) => {
                const newLang = $(e.currentTarget).data('gt-lang');
                if (newLang) {
                    const formatted = newLang.replace('-', '_');
                    this.storages().forEach(storage => {
                        try {
                            storage.setItem(this.key, formatted);
                        } catch (e) {}
                    });
                }
                this.clearPluginData();
            });
        }
    };

    const PendingSession = {
        key: 'ths:pending-session',
        ttlMs: 30 * 60 * 1000,
        now() {
            return Date.now();
        },
        generateId() {
            return `${this.now()}-${Math.random().toString(36).slice(2, 10)}`;
        },
        storages() {
            return [sessionStorage, localStorage];
        },
        load() {
            for (const storage of this.storages()) {
                try {
                    const raw = storage.getItem(this.key);
                    if (!raw) continue;
                    const data = JSON.parse(raw);
                    if (!data || !data.ts || (this.now() - data.ts) > this.ttlMs) {
                        this.clear();
                        return null;
                    }
                    if (data.locale && LocaleSession.current() && data.locale !== LocaleSession.current()) {
                        this.clear();
                        return null;
                    }
                    return data;
                } catch (e) {
                    storage.removeItem(this.key);
                }
            }
            return null;
        },
        save(payload) {
            const current = this.load();
            const data = {
                sessionId: payload.sessionId || current?.sessionId || this.generateId(),
                ts: this.now(),
                source: payload.source || 'analysis',
                step: payload.step || 'result',
                activeData: payload.activeData,
                resultHtml: payload.resultHtml || '',
                question: payload.question || '',
                payload: payload.payload || null,
                loadingTexts: payload.loadingTexts ?? analysisLoadingTexts,
                locale: payload.locale ?? LocaleSession.current()
            };
            const raw = JSON.stringify(data);
            this.storages().forEach(storage => {
                try {
                    storage.setItem(this.key, raw);
                } catch (e) {}
            });
            return data;
        },
        clear() {
            this.storages().forEach(storage => {
                try {
                    storage.removeItem(this.key);
                } catch (e) {}
            });
        },
        read() {
            return this.load();
        }
    };

    const Resume = {
        applied: false,
        apply() {
            const snapshot = PendingSession.read();
            if (!snapshot) return false;
            const $resultBox = $('#ths-result');
            if (!$resultBox.length) return false;
            if (!$resultBox.is(':visible') && !snapshot.resultHtml) return false;

            analysisLoadingTexts = snapshot.loadingTexts && typeof snapshot.loadingTexts === 'object'
                ? snapshot.loadingTexts
                : null;
            $('#ths-landing-wrapper').addClass('is-focused');
            $('#ths-form').length && $('#ths-form')[0].reset();
            if (snapshot.activeData) {
                const dob = snapshot.activeData.dob || '';
                const restoredDob = DateValidator.validate(dob, true);
                $('#ths-name').val(snapshot.activeData.name || '');
                $('#ths-dob').val(restoredDob.valid && restoredDob.value ? restoredDob.value : dob);
            }
            $resultBox.html(snapshot.resultHtml).show();

            const $chatBody = $('#ths-chat-body');
            if ($chatBody.length && snapshot.activeData) {
                $chatBody
                    .attr('data-name', snapshot.activeData.name || '')
                    .attr('data-dob', snapshot.activeData.dob || '');
            }

            if (snapshot.source === 'analysis') {
                $('#ths-step-1-wrap').show();
                $('#ths-step-2-wrap').show();
                $('#ths-action-next').show();
                $('#ths-action-footer, #ths-disclaimer').hide();
            } else {
                $('#ths-step-1-wrap').show();
                $('#ths-step-2-wrap').hide();
                $('#ths-action-next').show();
                $('#ths-action-footer, #ths-disclaimer').hide();
            }

            if (snapshot.question) {
                $('#ths-user-question').val(snapshot.question);
                $('#ths-qa-input-area').show();
                $('#ths-btn-deep-analyze').hide();
                $('#ths-btn-qa-analyze').show().find('.ths-btn-text').text(getQaSendLabel());
                $('#ths-btn-qa-cancel').show();
            }

            $('.ths-btn-reset').prop('disabled', false).fadeIn(200);
            PendingSession.clear();
            this.applied = true;
            const $targetBtn = $('#ths-btn-deep-analyze:visible').first().length
                ? $('#ths-btn-deep-analyze:visible').first()
                : $('#ths-btn-qa-analyze:visible').first();
            if ($targetBtn.length) {
                $('html, body').animate({ scrollTop: $targetBtn.offset().top - 120 }, 300);
            }
            return true;
        }
    };

    function getQaDefaultLabel() {
        return $('#ths-btn-qa-analyze').data('default-label') || $('#ths-btn-qa-analyze .ths-btn-text').text();
    }

    function getQaSendLabel() {
        return $('#ths-btn-qa-analyze').data('send-label') || getQaDefaultLabel();
    }

    const FormHandler = {
        init() {
            let activeUserData = null;

            $('.faq-q').on('click', function () {
                const $a = $(this).next('.faq-a');
                const $c = $(this).find('.faq-chevron');
                $a.toggleClass('open');
                $c.toggleClass('open');
            });

            $(document).on('click', '.ths-btn-reset', function () {
                $(this).prop('disabled', true).fadeOut(200);
                $('#ths-landing-wrapper').removeClass('is-focused');
                $('#ths-result').slideUp(300, function () {
                    $(this).empty();
                });
                $('#ths-form')[0].reset();
                if ($('#ths-form-section').length) {
                    $('html,body').animate({
                        scrollTop: $('#ths-form-section').offset().top - 80
                    }, 500);
                }
                PendingSession.clear();
                activeUserData = null;
            });

            $('#ths-name').on('keydown', function (e) {
                const allowedKeys = [8, 9, 13, 27, 32, 37, 38, 39, 40, 45, 46];
                if (allowedKeys.includes(e.keyCode) || e.ctrlKey || e.metaKey) return true;
                const char = e.key || String.fromCharCode(e.keyCode || e.which);
                if (/^[\p{L}\s'’.-]$/u.test(char)) return true;
                return false;
            }).on('input', function () {
                const $el = $(this);
                const $error = $('#ths-error-name');
                const val = $el.val();

                if (val.length > NAME_MAX_LENGTH) {
                    $el.addClass('is-error');
                    $error.text(Messages.nameTooLong());
                    return;
                }

                $el.removeClass('is-error');
                $error.text('');
            }).on('blur', function () {
                const $input = $(this);
                const value = Validator.normalizeName($input.val());
                $input.val(value);
                Validator.name('ths-name', 'ths-error-name');
            });
            DateValidator.bind('ths-dob', 'ths-error-dob');

            UI.$form.on('submit', function (e) {
                e.preventDefault();
                const name = Validator.name('ths-name', 'ths-error-name');
                const dobResult = DateValidator.validateInput('ths-dob', 'ths-error-dob');
                const dob = dobResult ? dobResult.iso : false;
                if (!name || !dob) return;

                Loading.set(true);
                UI.$resultBox.hide().empty();
                $('.ths-btn-reset').fadeOut(200).prop('disabled', true);

                fetch(ths.api_url + 'calculate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ full_name: name, dob: dob, locale: getLocale() })
                })
                    .then(response => response.json())
                    .then(res => {
                        if (!res.success) {
                            $('#ths-error-name').text(res.message || 'An error occurred.');
                            Loading.set(false);
                            if ($('#ths-landing-wrapper').hasClass('is-focused')) {
                                $('.ths-btn-reset').prop('disabled', false).fadeIn(400);
                            }
                            return;
                        }

                        analysisLoadingTexts = res.loading_texts && typeof res.loading_texts === 'object'
                            ? res.loading_texts
                            : null;
                        $('#ths-landing-wrapper').addClass('is-focused');

                        UI.$resultBox.html(res.html).fadeIn(300, function () {
                            $('html,body').animate({scrollTop: UI.$resultBox.offset().top - 60}, 500);
                        });
                        const rawLines = $('#ths-chat-body').data('lines');
                        const lines = Array.isArray(rawLines) ? rawLines : (typeof rawLines === 'string' ? JSON.parse(rawLines) : []);

                        Typewriter.run(lines, () => {
                            Loading.set(false);
                            $('#ths-action-next').fadeIn(400);
                            $('.ths-btn-reset').prop('disabled', false).fadeIn(400);
                        });
                    })
                    .catch(error => {
                        $('#ths-error-name').text('Connection error. Please try again.');
                        Loading.set(false);
                        if ($('#ths-landing-wrapper').hasClass('is-focused')) {
                            $('.ths-btn-reset').prop('disabled', false).fadeIn(400);
                        }
                    });
            });

            const getActiveThsData = () => {
                const $chatBody = $('#ths-chat-body');
                return {
                    name: $chatBody.data('name') || '',
                    dob: $chatBody.data('dob') || ''
                };
            };

            const setAiButtonLoading = ($btn, isLoading, text) => {
                const $loading = $btn.find('.ths-btn-loading');
                if (isLoading) {
                    if (text) $loading.html(`<span class="ths-spinner"></span> ${text}`);
                    $btn.addClass('ths-loading').attr('disabled', true);
                    $btn.find('.ths-btn-text').hide();
                    $loading.show();
                } else {
                    $btn.removeClass('ths-loading').removeAttr('disabled');
                    $btn.find('.ths-btn-text').show();
                    $loading.hide();
                }
            };

            const runThsAiAnalysis = async ({ isQaMode = false, question = '' } = {}) => {
                const data = getActiveThsData();
                const $deepBtn = $('#ths-btn-deep-analyze');
                const $qaBtn = $('#ths-btn-qa-analyze');
                const $cancelBtn = $('#ths-btn-qa-cancel');
                const $err = $('#ths-error-api');
                const $activeBtn = isQaMode ? $qaBtn : $deepBtn;
                const $otherBtn = isQaMode ? $deepBtn : $qaBtn;
                const requestPayload = {
                    full_name: data.name,
                    dob: data.dob,
                    user_question: question,
                    is_qa_mode: isQaMode,
                    locale: getLocale(),
                    ths_hp_tname: $('#ths-hp-tname').val() || ''
                };

                const savePendingAnalysis = () => {
                    PendingSession.save({
                        source: 'analysis',
                        step: 'result',
                        activeData: data,
                        resultHtml: $('#ths-result').html(),
                        question,
                        payload: requestPayload
                    });
                };

                if (!data.name || !data.dob) {
                    $err.text('Missing date of birth or full name.').show();
                    return;
                }

                $err.hide().text('');
                $otherBtn.hide();
                $cancelBtn.hide();

                const loadingMode = isQaMode ? 'qa' : 'deep';
                const localizedLoadingTexts = analysisLoadingTexts?.[loadingMode];
                const loadingTexts = Array.isArray(localizedLoadingTexts) && localizedLoadingTexts.length
                    ? localizedLoadingTexts
                    : [];
                let loadingStep = 0;
                setAiButtonLoading($activeBtn, true, loadingTexts[loadingStep] || '');
                const loadingInterval = loadingTexts.length > 1
                    ? setInterval(() => {
                        if (loadingStep < loadingTexts.length - 1) {
                            loadingStep++;
                            $activeBtn.find('.ths-btn-loading').html(`<span class="ths-spinner"></span> ${loadingTexts[loadingStep]}`);
                        }
                    }, 1500)
                    : null;

                const abortController = new AbortController();
                const timeoutId = setTimeout(() => abortController.abort(), 240000);
                const cleanup = () => {
                    clearInterval(loadingInterval);
                    clearTimeout(timeoutId);
                };

                try {
                    const res = await fetch(ths.api_url + 'analyze', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(requestPayload),
                        signal: abortController.signal
                    });

                    const response = await res.json();
                    cleanup();

                    if (response.success && response.html_detail) {
                        $('#ths-analysis-display').html(response.html_detail);
                        $('#ths-step-2-wrap').slideDown(400, () => {
                            $('#ths-disclaimer').fadeIn(400);
                            $('#ths-action-footer').fadeIn(400);
                        });
                        $('html, body').animate({ scrollTop: $('#ths-analysis-display').offset().top - 100 }, 300);
                        PendingSession.clear();
                        setAiButtonLoading($activeBtn, false);
                        if (!isQaMode) {
                            $deepBtn.hide();
                            $qaBtn.show().find('.ths-btn-text').text(getQaDefaultLabel());
                        } else {
                            $('#ths-qa-input-area').hide();
                            $qaBtn.find('.ths-btn-text').text(getQaDefaultLabel());
                            $qaBtn.show();
                        }
                    } else {
                        setAiButtonLoading($activeBtn, false);
                        $otherBtn.show();
                        if (isQaMode) $cancelBtn.show();
                        if (response.code === 'login_required') {
                            savePendingAnalysis();
                            Auth.openLogin();
                        } else if (response.out_of_credits && window.VibhhPayModal) {
                            PendingSession.save({
                                force: true,
                                source: 'than-so-hoc-payment',
                                step: 'result',
                                activeData: data,
                                resultHtml: $('#ths-result').html(),
                                question,
                                payload: requestPayload
                            });
                            window.VibhhPayModal.show(response.balance || 0);
                        } else {
                            $err.text(response.message || 'Unable to analyze. Please try again.').show();
                        }
                    }
                } catch (err) {
                    cleanup();
                    setAiButtonLoading($activeBtn, false);
                    $otherBtn.show();
                    if (isQaMode) $cancelBtn.show();
                    $err.text(err.name === 'AbortError'
                        ? 'Request timed out. Please try again.'
                        : 'Connection error. Please check your internet and try again.').show();
                }
            };

            $(document).on('click', '#ths-btn-deep-analyze', function() {
                runThsAiAnalysis({ isQaMode: false });
            });

            $(document).on('click', '#ths-btn-qa-analyze', function() {
                const $qaArea = $('#ths-qa-input-area');
                const $deepBtn = $('#ths-btn-deep-analyze');
                const $btn = $(this);
                const $cancelBtn = $('#ths-btn-qa-cancel');
                const $err = $('#ths-error-api');

                if ($qaArea.is(':hidden')) {
                    $deepBtn.hide();
                    $qaArea.slideDown(250);
                    $btn.find('.ths-btn-text').text(getQaSendLabel());
                    $cancelBtn.show();
                    $qaArea.find('textarea').focus();
                    return;
                }

                const question = $('#ths-user-question').val().trim();
                if (question.length < 10) {
                    $err.text('Please ask a more meaningful question.').show();
                    return;
                }

                runThsAiAnalysis({ isQaMode: true, question });
            });

            $(document).on('click', '#ths-btn-qa-cancel', function() {
                $('#ths-qa-input-area').slideUp(200);
                $('#ths-btn-deep-analyze').show();
                $('#ths-btn-qa-analyze').show().find('.ths-btn-text').text(getQaDefaultLabel());
                $(this).hide();
                $('#ths-error-api').hide().text('');
            });

            $(document).on('click', '.ths-sq-btn', function() {
                $('#ths-user-question').val($(this).data('q') || '');
                $('#ths-error-api').hide().text('');
            });
        }
    };

    const Animation = {
        observer: null,

        init() {
            this.createObserver();
            this.observeElements();
        },

        createObserver() {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        this.observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -40px 0px'
            });
        },

        observeElements() {
            const sections = document.querySelectorAll(
                '.ths-landing .lp-section, .ths-landing .intro-card, .ths-landing .ind-card, .ths-landing .faq-item'
            );
            sections.forEach(el => this.observer.observe(el));

            const cards = document.querySelectorAll('.ths-landing .intro-card, .ths-landing .ind-card');
            cards.forEach((el, i) => {
                el.style.transitionDelay = `${i * 80}ms`;
                this.observer.observe(el);
            });
        },

        destroy() {
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
        }
    };

    const NumberSwitcher = {
        init() {
            window.thsSwitchNum = this.switchNumber.bind(this);
        },

        switchNumber(num, btnElement) {
            document.querySelectorAll('.ths-num-content').forEach(function (el) {
                el.classList.remove('active');
            });
            document.querySelectorAll('.ths-num-tab').forEach(function (el) {
                el.classList.remove('active');
            });
            document.getElementById('ths-mean-' + num).classList.add('active');
            btnElement.classList.add('active');
        }
    };

    $(document).on('click', '#ths-btn-comment', function () {
        const $comments = $('#comments');
        if (!$comments.length) return;

        if ($comments.is(':visible')) {
            $comments.slideUp(300);
            $(this).removeClass('active');
        } else {
            $comments.slideDown(400, function () {
                $('html, body').animate({scrollTop: $comments.offset().top - 20}, 400);
            });
            $(this).addClass('active');
        }
    });

    TabHandler.init();
    FormHandler.init();
    Animation.init();
    NumberSwitcher.init();
    LocaleSession.init();
    LocaleSession.bindLanguageLinks();
    Resume.apply();
});
