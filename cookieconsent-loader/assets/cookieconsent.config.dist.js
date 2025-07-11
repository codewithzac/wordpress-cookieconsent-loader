// CookieConsent configuration object
// Configuration reference: https://cookieconsent.orestbida.com/reference/configuration-reference.html
let ccConfig = {

  autoShow: true,
  disablePageInteraction: true,
  mode: 'opt-in',

  guiOptions: {
    consentModal: {
      layout: 'box',
      position: 'bottom right',
      flipButtons: false,
      equalWeightButtons: true  
    },
    preferencesModal: {
      layout: 'box',
      position: 'right',
      flipButtons: false,
      equalWeightButtons: true
    }
  },

  categories: {
    necessary: {
      enabled: true,
      readOnly: true
    },
    analytics: {},
    marketing: {}
  },

  language: {
    default: 'en',
    translations: {
      en: {
        consentModal: {
          title: 'We use cookies',
          description: 'Cookie modal description',
          acceptAllBtn: 'Accept all',
          acceptNecessaryBtn: 'Reject all',
          showPreferencesBtn: 'Manage Individual preferences'
        },
        preferencesModal: {
          title: 'Manage cookie preferences',
          acceptAllBtn: 'Accept all',
          acceptNecessaryBtn: 'Reject all',
          savePreferencesBtn: 'Accept current selection',
          closeIconLabel: 'Close modal',
          sections: [
            {
              title: 'Somebody said ... cookies?',
              description: 'I want one!'
            },
            {
              title: 'Strictly Necessary <span class="pm__badge">Always Enabled</span>',
              description: 'These cookies are essential for the proper functioning of the website and cannot be disabled.',
              linkedCategory: 'necessary'
            },
            {
              title: 'Analytics and Performance',
              description: 'These cookies collect information about how you use our website. All of the data is anonymized and cannot be used to identify you.',
              linkedCategory: 'analytics'
            },
            {
              title: 'Marketing and Social',
              description: 'These cookies collect information about how you use our website, in order to personalize ads and improve marketing campaigns.',
              linkedCategory: 'marketing'
            },
            {
              title: 'More information',
              description: 'For any queries in relation to our policy on cookies and your choices, please <a href="#contact-page">contact us</a>'
            }
          ]
        }
      }
    }
  },

  // Trigger GTM dataLayer and WP Consent API events when updating consent
  // These are optional, but required if using the "GTM Consent for CookieConsent" GTM template or WP Consent API integration
  onFirstConsent: function(detail) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'onFirstConsent',
      consentCategories: detail.cookie.categories,
      consentServices: detail.cookie.services
    });
    updateWPConsent(detail);
  },
  onChange: function(detail) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'onChange',
      consentCategories: detail.cookie.categories,
      consentServices: detail.cookie.services
    });
    updateWPConsent(detail);
  }
};

// Helper function to update WP Consent API
function updateWPConsent(detail) {
  if (typeof wp_set_consent !== 'function' || typeof ccload_consent_api_mapping === 'undefined') {
    return;
  }

  for (const ccCategory in ccload_consent_api_mapping) {
    const wpCategory = ccload_consent_api_mapping[ccCategory];
    if (wpCategory) {
      const consent = detail.cookie.categories.includes(ccCategory) ? 'allow' : 'deny';
      wp_set_consent(wpCategory, consent);
    }
  }
}

// [Optional] load custom CSS
// document.body.classList.add('cc--custom');

// Load CookieConsent
CookieConsent.run(ccConfig);
