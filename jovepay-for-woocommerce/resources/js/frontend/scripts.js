( function( $ ) {
    $( document ).ready( function() {
        // How to setup
        var targetRow = jQuery( 'input#woocommerce_jovepay_enabled' ).closest( 'tr' );
        var newRow = jQuery( 
            `<tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="new_field">${jpwc.i18n.howToSetup}</label>
                </th>
                <td class="forminp">
                    <a href="https://www.jovepay.com/docs/plugins/woocommerce/?utm_source=jpwc&utm_medium=how-to-setup" target="_blank">
                        ${jpwc.i18n.documentation}
                    </a>
                </td>
            </tr>` );
        targetRow.after( newRow );
    } );
} )( jQuery );
