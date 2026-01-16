jQuery( document ).ready( function ($) {
  if ($( '.wpes-settings' ).length > 0) {
    /**
     * Settings panel
     */
    let keys = 'enable_history,smtp-enabled,enable-smime,enable-dkim,smtp-is_html'.split( ',' );
    keys.forEach( (selector) => {
      $( '#' + selector ).on( 'change', function (e) { // we need 'function' here for 'this'.
        let target_id = e.target.id;
        $( '.on-' + target_id ).toggle( $( this ).is( ':checked' ) );
        $( '.not-' + target_id ).toggle( !$( this ).is( ':checked' ) );
      } ).trigger( 'change' );
    } );

    let preventInfinite = false;
    $( '.on-regexp-test' ).each( function () { // we need 'function' here for 'this'.
      (
        (field, regexp, label) => {
          $( '#' + field ).on( 'change keyup blur paste', function () {
            let value = $( this ).val() || null;
            if ($( this ).is( '[type=checkbox],[type=radio]' )) {
              if (!preventInfinite) {
                preventInfinite = true;
                let name = $( this ).attr( 'name' );
                let siblings = $( this ).closest( '.postbox' ).find( '[name="' + name + '"]' ).not( this );
                siblings.trigger( 'change' );
                preventInfinite = false;
              }
              if (!$( this ).is( ':checked' )) {
                value = null;
              }
            }
            label.toggle( null !== (
              value || ''
            ).match( new RegExp( regexp, 'i' ) ) );
          } ).trigger( 'change' );
        }
      )( $( this ).attr( 'data-field' ), $( this ).attr( 'data-regexp' ), $( this ) );
    } );
  }

  if ($( '.wpes-emails' ).length > 0) {
    /**
     * Emails panel
     */

    const enableViewer = function () {
      $( '#mail-viewer' ).removeClass('hidden');
    }

    // Function to switch to a specific view
    const switchToView = function (viewClass) {
      let activeEmail = $( '.email-item.active' );
      if (activeEmail.length === 0) return;

      let id = '#' + activeEmail.attr( 'id' ).replace( 'email-', 'email-data-' );
      let that = $( id );

      // Remove all show-* classes
      activeEmail.removeClass( (index, className) => (
        className.match( /(^|\s)show-\S+/g ) || []
      ).join( ' ' ) );
      that.removeClass( (index, className) => (
        className.match( /(^|\s)show-\S+/g ) || []
      ).join( ' ' ) );

      // Add the new show-* class
      activeEmail.add( that ).addClass( 'show-' + viewClass );

      // Update tab active states
      $( '.mail-tab' ).removeClass( 'active' ).attr( 'aria-selected', 'false' );
      $( '.mail-tab[data-view="' + viewClass + '"]' ).addClass( 'active' ).attr( 'aria-selected', 'true' );

      $( window ).trigger( 'resize' );
    };

    // Tab click handler
    $( '.mail-tab' ).on( 'click', function (e) {
      e.preventDefault();
      let view = $( this ).attr( 'data-view' );
      switchToView( view );
    } );

    // Email row click handler (cycling through views)
    $( '.email-item' ).on( 'click', function (e) { // we need 'function' here for 'this'.
      if ($( e.target ).is( 'a.dashicons-download' )) {
        e.stopPropagation();
        return true;
      }

      $( this ).addClass( 'active' ).siblings().removeClass( 'active' ).removeClass( (index, className) => (
        className.match( /(^|\s)show-\S+/g ) || []
      ).join( ' ' ) );

      let id = '#' + $( '.email-item.active' ).attr( 'id' ).replace( 'email-', 'email-data-' );
      let that = $( id );
      $( '#mail-data-viewer .email-data' ).removeClass( (index, className) => (
        className.match( /(^|\s)show-\S+/g ) || []
      ).join( ' ' ) );

      // Click to cycle through the views.
      let currentView = null;
      if ($( this ).is( '.show-body' )) {
        currentView = 'body-source';
      } else if ($( this ).is( '.show-body-source' )) {
        currentView = 'headers';
      } else if ($( this ).is( '.show-headers' )) {
        currentView = 'alt-body';
      } else if ($( this ).is( '.show-alt-body' )) {
        currentView = 'debug';
      } else {
        currentView = 'body';
      }
      enableViewer();
      switchToView( currentView );
    } );

    $( window ).bind( 'resize', function () { // we need 'function' here for 'this'.
      $( '.autofit' ).each( function () {
        $( this ).css( 'width', $( this ).parent().innerWidth() );
        $( this ).css( 'height', $( this ).parent().innerHeight() );
      } );
    } ).trigger( 'resize' );

    var $pageSizeSelects = $( '#wpes-page-size, #wpes-page-size-bottom' );
    $pageSizeSelects.each( function () {
      var $select = $( this );
      $select.on( 'change', function () {
        var currentUrl = new URL( window.location.href );
        currentUrl.searchParams.set( '_limit', this.value );
        currentUrl.searchParams.set( '_page', '0' ); // Reset to first page
        window.location.href = currentUrl.toString();
      } );
    } );
  }

  if ($( '.wpes-admins' ).length > 0) {
    /**
     * Admins panel
     */
    let t = function () { // we need 'function' here for 'this'.
      if (/^\/[\s\S]+\/[i]?$/.test( (
        $( this ).val() || ''
      ) )) {
        let that = this;
        let re = $( that ).val();

        re = re.split( re.charAt( 0 ) );
        re = new RegExp( re[1], re[2] );

        $( '.a-fail' ).each( function () {
          $( this ).toggleClass( 'match', re.test( (
            $( this ).text() || ''
          ) ) );
        } );
      } else {
        $( '.a-fail' ).removeClass( 'match' );
      }
    };
    $( '.a-regexp' ).bind( 'blur', function () { // we need 'function' here for 'this'.
      let val = (
        $( this ).val() || ''
      );
      if ('' === val) {
        return $( this ).removeClass( 'error match' );
      }
      $( this ).toggleClass( 'error', !/^\/[\s\S]+\/[i]?$/.test( val ) ).not( '.error' ).addClass( 'match' );
    } ).bind( 'focus', function (e) { // we need 'function' here for 'this'.
      $( '.a-fail,.a-regexp' ).removeClass( 'match' );
      $( this ).removeClass( 'error match' );
      t.apply( this, [e] );
    } ).bind( 'keyup', t );
  }
} );
