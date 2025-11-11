/* global jQuery, ajaxurl, spotplayBuyerReport */
(function($){
  function getAjaxUrl(){
    if (typeof ajaxurl !== 'undefined' && ajaxurl) { return ajaxurl; }
    if (typeof spotplayBuyerReport !== 'undefined' && spotplayBuyerReport.ajaxurl) { return spotplayBuyerReport.ajaxurl; }
    return (window.location.origin || '') + '/wp-admin/admin-ajax.php';
  }

  function submitSearch(page){
    var $f = $('#spotplay-buyer-search');
    if (!$f.length) { return; }
    var data = {
      action: 'spotplay_buyer_report_search',
      nonce: $f.find('input[name=nonce]').val() || '',
      q: $f.find('input[name=q]').val() || '',
      paged: page || 1
    };
    $.post(getAjaxUrl(), data)
      .done(function(html){ $('#spotplay-buyer-report .results').html(html); })
      .fail(function(){ alert('خطای ارتباط با سرور. لطفاً دوباره تلاش کنید.'); });
  }

  // Click on search button
  $(document).on('click', '#spotplay-buyer-search-btn', function(e){
    e.preventDefault();
    submitSearch(1);
    return false;
  });

  // Enter key in search input triggers search
  $(document).on('keydown', '#spotplay-buyer-search input[name=q]', function(e){
    if (e.key === 'Enter') {
      e.preventDefault();
      submitSearch(1);
      return false;
    }
  });

  // Pagination click (AJAX)
  $(document).on('click', '#spotplay-buyer-report .pagination a[data-page]', function(e){
    e.preventDefault();
    var page = parseInt($(this).data('page'), 10) || 1;
    submitSearch(page);
    return false;
  });

  // Copy license to clipboard with feedback
  $(document).on('click', '.copy-license-btn', function(e){
    e.preventDefault();
    var $btn = $(this);
    var text = $btn.data('license');
    if (!text) { return false; }
    var done = function(){
      var old = $btn.text();
      $btn.text('کپی شد!');
      setTimeout(function(){ $btn.text(old); }, 1200);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(String(text)).then(done).catch(function(){ done(); });
    } else {
      var $tmp = $('<textarea>').css({position:'fixed',top:'-1000px'}).val(String(text));
      $('body').append($tmp);
      $tmp.select();
      try { document.execCommand('copy'); } catch(e) {}
      $tmp.remove();
      done();
    }
    return false;
  });
})(jQuery);