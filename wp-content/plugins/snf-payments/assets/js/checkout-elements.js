/**
 * SNF Payments — Stripe Payment Element + multi-step checkout.
 *
 * Confirms the PaymentIntent before Woo checkout submit so we never hit
 * Woo's checkout_place_order_success + return-false "Invalid response" path.
 */
(function ($) {
  'use strict';

  if (typeof snfPayments === 'undefined') {
    return;
  }

  var stripe = null;
  var elements = null;
  var paymentElement = null;
  var clientSecret = null;
  var confirming = false;
  var paymentConfirmed = false;

  function showError(message) {
    var $el = $('#snf-payment-errors');
    if (!$el.length) {
      return;
    }
    if (message) {
      $el.text(message).prop('hidden', false);
    } else {
      $el.text('').prop('hidden', true);
    }
  }

  function ensureStripe() {
    if (stripe) {
      return stripe;
    }
    if (!window.Stripe || !snfPayments.publishableKey) {
      return null;
    }
    stripe = window.Stripe(snfPayments.publishableKey);
    return stripe;
  }

  function mountPaymentElement(secret) {
    var s = ensureStripe();
    if (!s || !secret) {
      return;
    }
    if (clientSecret === secret && paymentElement) {
      return;
    }
    clientSecret = secret;
    paymentConfirmed = false;
    if (paymentElement) {
      try {
        paymentElement.unmount();
      } catch (e) {
        /* ignore */
      }
      paymentElement = null;
      elements = null;
    }
    elements = s.elements({
      clientSecret: secret,
      appearance: {
        theme: 'stripe',
        variables: {
          colorPrimary: '#1a1a1a',
          borderRadius: '2px',
          fontFamily: 'inherit',
        },
      },
    });
    paymentElement = elements.create('payment');
    var mount = document.getElementById('snf-payment-element');
    if (mount) {
      mount.innerHTML = '';
      paymentElement.mount('#snf-payment-element');
    }
  }

  function prepareIntentFromCart() {
    if (!snfPayments.prepareIntent) {
      return;
    }
    $.post(snfPayments.prepareIntent, { security: snfPayments.security })
      .done(function (json) {
        if (json && json.success && json.data && json.data.client_secret) {
          if (json.data.publishable_key) {
            snfPayments.publishableKey = json.data.publishable_key;
          }
          mountPaymentElement(json.data.client_secret);
          if (json.data.payment_intent_id) {
            $('#snf_payment_intent_id').val(json.data.payment_intent_id);
          }
        }
      })
      .fail(function (xhr) {
        var msg = snfPayments.i18n.payError;
        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
          msg = xhr.responseJSON.data.message;
        }
        showError(msg);
      });
  }

  function syncBillingHidden() {
    var name = $('input[name="snf_apply_name"]').val() || '';
    var email = $('input[name="snf_apply_email"]').val() || '';
    var phone = $('input[name="snf_apply_phone"]').val() || '';
    var zip = $('input[name="snf_apply_billing_zip"]').val() || '';
    var city = $('input[name="snf_apply_city"]').val() || '';
    var org = $('input[name="snf_apply_org"]').val() || '';
    var parts = name.trim().split(/\s+/);
    var first = parts.shift() || name;
    var last = parts.join(' ') || '';

    function set(sel, val) {
      var $f = $(sel);
      if ($f.length) {
        $f.val(val).trigger('change');
      }
    }

    set('#billing_first_name', first);
    set('#billing_last_name', last);
    set('#billing_email', email);
    set('#billing_phone', phone);
    set('#billing_postcode', zip);
    set('#billing_city', city);
    set('#billing_company', org);
    if (!$('#billing_country').val()) {
      set('#billing_country', 'US');
    }
  }

  function checkoutReturnUrl() {
    var url = window.location.href.split('#')[0];
    try {
      var u = new URL(url);
      u.searchParams.set('snf_stripe_return', '1');
      return u.toString();
    } catch (e) {
      return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'snf_stripe_return=1';
    }
  }

  function confirmPaymentThenSubmit($form) {
    var s = ensureStripe();
    if (!s || !elements || !clientSecret) {
      showError(snfPayments.i18n.cardLoad || snfPayments.i18n.payError);
      prepareIntentFromCart();
      return;
    }

    confirming = true;
    showError('');
    $('#place_order').prop('disabled', true).addClass('is-processing');

    s.confirmPayment({
      elements: elements,
      confirmParams: {
        return_url: checkoutReturnUrl(),
        payment_method_data: {
          billing_details: {
            name:
              $('input[name="snf_apply_card_name"]').val() ||
              $('input[name="snf_apply_name"]').val() ||
              '',
            email: $('input[name="snf_apply_email"]').val() || '',
            phone: $('input[name="snf_apply_phone"]').val() || '',
            address: {
              postal_code: $('input[name="snf_apply_billing_zip"]').val() || '',
              city: $('input[name="snf_apply_city"]').val() || '',
            },
          },
        },
      },
      redirect: 'if_required',
    }).then(function (result) {
      if (result.error) {
        showError(result.error.message || snfPayments.i18n.payError);
        confirming = false;
        paymentConfirmed = false;
        $('#place_order').prop('disabled', false).removeClass('is-processing');
        return;
      }

      var intent = result.paymentIntent;
      if (intent && intent.id) {
        $('#snf_payment_intent_id').val(intent.id);
      }
      paymentConfirmed = true;
      confirming = false;
      $('#place_order').prop('disabled', false).removeClass('is-processing');
      $form.trigger('submit');
    });
  }

  function initSteps() {
    var $root = $('[data-snf-checkout-steps]');
    if (!$root.length) {
      return;
    }

    var $panels = $root.find('[data-step-panel]');
    var $tabs = $root.find('[data-step]');
    var $indicators = $root.find('[data-step-indicator]');

    function goTo(step) {
      step = String(step);
      $panels.each(function () {
        var $p = $(this);
        var active = String($p.data('step-panel')) === step;
        $p.toggleClass('is-active', active).prop('hidden', !active);
      });
      $tabs.each(function () {
        var $t = $(this);
        var active = String($t.data('step')) === step;
        $t.toggleClass('is-active', active).attr('aria-selected', active ? 'true' : 'false');
        if (active) {
          $t.attr('aria-current', 'step');
        } else {
          $t.removeAttr('aria-current');
        }
      });
      $indicators.each(function () {
        var $i = $(this);
        $i.toggleClass('is-active', String($i.data('step-indicator')) === step);
      });

      var isPay =
        step === 'pay' ||
        step === '4' ||
        ($panels.length && String($panels.last().data('step-panel')) === step);

      if (isPay) {
        var $gw = $('input[name="payment_method"][value="' + snfPayments.gatewayId + '"]');
        if ($gw.length && !$gw.is(':checked')) {
          $gw.prop('checked', true).trigger('change');
        }
        prepareIntentFromCart();
      }
    }

    $root.on('click', '[data-snf-next-step]', function (e) {
      e.preventDefault();
      var $panel = $(this).closest('[data-step-panel]');
      var inputs = $panel.find('input, select, textarea').filter('[required]');
      var valid = true;
      inputs.each(function () {
        if (!this.checkValidity()) {
          this.reportValidity();
          valid = false;
          return false;
        }
      });
      if (!valid) {
        return;
      }
      syncBillingHidden();
      var next = $panel.nextAll('[data-step-panel]').first();
      if (next.length) {
        goTo(next.data('step-panel'));
      }
    });

    $root.on('click', '[data-snf-prev-step]', function (e) {
      e.preventDefault();
      var $panel = $(this).closest('[data-step-panel]');
      var prev = $panel.prevAll('[data-step-panel]').first();
      if (prev.length) {
        goTo(prev.data('step-panel'));
      }
    });

    $root.on('click', '[data-step]', function (e) {
      e.preventDefault();
      goTo($(this).data('step'));
    });

    goTo($panels.first().data('step-panel') || '1');
  }

  function resumeAfterStripeRedirect() {
    var params;
    try {
      params = new URLSearchParams(window.location.search);
    } catch (e) {
      return;
    }
    if (params.get('snf_stripe_return') !== '1' && !params.get('payment_intent')) {
      return;
    }

    var pi = params.get('payment_intent') || '';
    var status = params.get('redirect_status') || '';
    if (pi) {
      $('#snf_payment_intent_id').val(pi);
    }

    // Land on Pay step so Place order is visible after 3DS.
    var $pay = $('[data-step-panel="pay"]');
    if ($pay.length) {
      $('[data-step-panel]').removeClass('is-active').prop('hidden', true);
      $pay.addClass('is-active').prop('hidden', false);
      $('[data-step], [data-step-indicator]').removeClass('is-active');
      $('[data-step="pay"], [data-step-indicator="pay"]').addClass('is-active');
    }

    if (status === 'succeeded' || status === 'processing' || pi) {
      paymentConfirmed = true;
      showError('');
    } else if (status === 'failed') {
      showError(snfPayments.i18n.payError);
      paymentConfirmed = false;
      prepareIntentFromCart();
    }
  }

  $(document.body).on('checkout_place_order_' + snfPayments.gatewayId, function () {
    syncBillingHidden();
    showError('');

    if (paymentConfirmed && $('#snf_payment_intent_id').val()) {
      return true;
    }

    if (confirming) {
      return false;
    }

    confirmPaymentThenSubmit($('form.checkout'));
    return false;
  });

  $(document.body).on('checkout_error', function () {
    confirming = false;
    paymentConfirmed = false;
    $('#place_order').prop('disabled', false).removeClass('is-processing');
  });

  $(document.body).on('updated_checkout', function () {
    if ($('[data-step-panel].is-active').data('step-panel') === 'pay') {
      if (!paymentConfirmed) {
        prepareIntentFromCart();
      }
    }
  });

  $(function () {
    initSteps();
    ensureStripe();
    resumeAfterStripeRedirect();
  });
})(jQuery);
