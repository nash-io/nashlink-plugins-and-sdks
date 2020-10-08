/* jshint browser: true, strict: false, maxlen: false, maxstatements: false */
(function() {

    function warn() {
      if(window.console && window.console.warn) {
        window.console.warn.apply(window.console, arguments);
      }
    }
  
    if(window.nashlink) {
      warn('nashlink.js attempted to initialize more than once.');
      return;
    }
  
    var iframe = document.createElement('iframe');
    iframe.name = 'nashlink';
    iframe.class = 'nashlink';
    iframe.setAttribute('allowtransparency', 'true');
    iframe.style.display = 'none';
    iframe.style.border = 0;
    iframe.style.position = 'fixed';
    iframe.style.top = 0;
    iframe.style.left = 0;
    iframe.style.height = '100%';
    iframe.style.width = '100%';
    iframe.style.zIndex = '2147483647';
  
    var onModalWillEnterMethod = function() {};
    var onModalWillLeaveMethod = function() {};
  
    function showFrame() {
      document.body.style.overflow = 'hidden';
      if (window.document.getElementsByName('nashlink').length === 0) {
        window.document.body.appendChild(iframe);
      }
      onModalWillEnterMethod();
      iframe.style.display = 'block';
    }
  
    function hideFrame() {
      onModalWillLeaveMethod();
      iframe.style.display = 'none';
      iframe = window.document.body.removeChild(iframe);
      document.body.style.overflow = 'auto';
  
    }
  
    function onModalWillEnter(customOnModalWillEnter) {
      onModalWillEnterMethod = customOnModalWillEnter;
    }
  
    function onModalWillLeave(customOnModalWillLeave) {
      onModalWillLeaveMethod = customOnModalWillLeave;
    }
  
    function showInvoice(response, params) {
      document.body.style.overflow = 'hidden';
      console.log(response.invoiceUrl);
      iframe.src = response.invoiceUrl;
      window.document.body.appendChild(iframe);      
    }
  
    window.addEventListener('load', function load() {
      warn('load');
      window.removeEventListener('load', load);
    });
  
    window.nashlink = {
      showInvoice: showInvoice,
      onModalWillEnter: onModalWillEnter,
      onModalWillLeave: onModalWillLeave
    };
  
  })();