<?php
/**
 * osCommerce Online Merchant
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license GNU General Public License; http://www.oscommerce.com/gpllicense.txt
 */
?>

<h1><?php echo HEADING_TITLE_CONFIRMATION; ?></h1>

<?php
  if ( isset($OSCOM_Payment->get()->form_action_url) ) {
    echo osc_draw_form('checkout_confirmation', $OSCOM_Payment->get()->form_action_url, 'post');
  } else {
    echo osc_draw_form('checkout_confirmation', osc_href_link('checkout', 'process', 'SSL'), 'post', null, true);
  }
?>

<div class="contentContainer">
  <h2><?php echo HEADING_SHIPPING_INFORMATION; ?></h2>

  <div class="contentText">
    <table border="0" width="100%" cellspacing="1" cellpadding="2">
      <tr>

<?php
  if ( $OSCOM_Order->hasShippingAddress() ) {
?>

        <td width="30%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td><?php echo '<strong>' . HEADING_DELIVERY_ADDRESS . '</strong> <a href="' . osc_href_link('checkout', 'shipping&address', 'SSL') . '"><span class="badge badge-warning">' . TEXT_EDIT . '</span></a>'; ?></td>
          </tr>
          <tr>
            <td><?php echo osc_address_label($OSCOM_Customer->getID(), $OSCOM_Order->getShippingAddress(), true, ' ', '<br />'); ?></td>
          </tr>

<?php
    if ( $OSCOM_Order->hasShipping() ) {
?>

          <tr>
            <td><?php echo '<strong>' . HEADING_SHIPPING_METHOD . '</strong> <a href="' . osc_href_link('checkout', 'shipping', 'SSL') . '"><span class="badge badge-warning">' . TEXT_EDIT . '</span></a>'; ?></td>
          </tr>
          <tr>
            <td><?php echo $OSCOM_Order->getShipping('title'); ?></td>
          </tr>
<?php
    }
?>

        </table></td>

<?php
  }
?>

        <td width="<?php echo ($OSCOM_Order->hasShipping() ? '70%' : '100%'); ?>" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">

<?php
  if ( $OSCOM_Order->getNumberOfTaxGroups() > 1 ) {
?>

          <tr>
            <td colspan="2"><?php echo '<strong>' . HEADING_PRODUCTS . '</strong> <a href="' . osc_href_link('cart') . '"><span class="badge badge-warning">' . TEXT_EDIT . '</span></a>'; ?></td>
            <td align="right"><strong><?php echo HEADING_TAX; ?></strong></td>
            <td align="right"><strong><?php echo HEADING_TOTAL; ?></strong></td>
          </tr>

<?php
  } else {
?>

          <tr>
            <td colspan="3"><?php echo '<strong>' . HEADING_PRODUCTS . '</strong> <a href="' . osc_href_link('cart') . '"><span class="badge badge-warning">' . TEXT_EDIT . '</span></a>'; ?></td>
          </tr>

<?php
  }

  foreach ( $_SESSION['cart']->get_products() as $p ) {
    echo '          <tr>' . "\n" .
         '            <td align="right" valign="top" width="30">' . $p['quantity'] . '&nbsp;x</td>' . "\n" .
         '            <td valign="top">' . $p['name'];

    if (STOCK_CHECK == 'true') {
      echo osc_check_stock($p['id'], $p['quantity']);
    }

    if ( isset($p['attributes']) && !empty($p['attributes']) ) {
      foreach ( $p['attributes'] as $pa ) {
        echo '<br /><nobr><small>&nbsp;<i> - ' . $pa['option'] . ': ' . $pa['value'] . '</i></small></nobr>';
      }
    }

    echo '</td>' . "\n";

    if ( $OSCOM_Order->getNumberOfTaxGroups() > 1 ) echo '            <td valign="top" align="right">' . osc_display_tax_value(osc_get_tax_rate($p['tax_class_id'], $OSCOM_Order->getTaxAddress('country_id'), $OSCOM_Order->getTaxAddress('zone_id'))) . '%</td>' . "\n";

    echo '            <td align="right" valign="top">' . $currencies->display_price($p['final_price'], osc_get_tax_rate($p['tax_class_id'], $OSCOM_Order->getTaxAddress('country_id'), $OSCOM_Order->getTaxAddress('zone_id')), $p['quantity']) . '</td>' . "\n" .
         '          </tr>' . "\n";
  }
?>

        </table></td>
      </tr>
    </table>
  </div>

  <h2><?php echo HEADING_BILLING_INFORMATION; ?></h2>

  <div class="contentText">
    <table border="0" width="100%" cellspacing="1" cellpadding="2">
      <tr>
        <td width="30%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td><?php echo '<strong>' . HEADING_BILLING_ADDRESS . '</strong> <a href="' . osc_href_link('checkout', 'payment&address', 'SSL') . '"><span class="badge badge-warning">' . TEXT_EDIT . '</span></a>'; ?></td>
          </tr>
          <tr>
            <td><?php echo osc_address_label($OSCOM_Customer->getID(), $OSCOM_Order->getBillingAddress(), true, ' ', '<br />'); ?></td>
          </tr>
          <tr>
            <td><?php echo '<strong>' . HEADING_PAYMENT_METHOD . '</strong> <a href="' . osc_href_link('checkout', 'payment', 'SSL') . '"><span class="badge badge-warning">' . TEXT_EDIT . '</span></a>'; ?></td>
          </tr>
          <tr>
            <td><?php echo $OSCOM_Order->getBilling('title'); ?></td>
          </tr>
        </table></td>
        <td width="70%" valign="top" align="right"><table border="0" cellspacing="0" cellpadding="2">

<?php
  foreach ( $OSCOM_Order->getTotals() as $t ) {
    echo '              <tr>' . "\n" .
         '                <td align="right">' . $t['title'] . '</td>' . "\n" .
         '                <td align="right">' . $t['text'] . '</td>' . "\n" .
         '              </tr>' . "\n";
  }
?>

        </table></td>
      </tr>
    </table>
  </div>

<?php
  if ( $OSCOM_Order->hasBilling() ) {
    if ($confirmation = $OSCOM_Payment->confirmation()) {
?>

  <h2><?php echo HEADING_PAYMENT_INFORMATION; ?></h2>

  <div class="contentText">
    <table border="0" cellspacing="0" cellpadding="2">
      <tr>
        <td colspan="4"><?php echo $confirmation['title']; ?></td>
      </tr>

<?php
      if (isset($confirmation['fields'])) {
        for ($i=0, $n=sizeof($confirmation['fields']); $i<$n; $i++) {
?>

      <tr>
        <td><?php echo osc_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
        <td class="main"><?php echo $confirmation['fields'][$i]['title']; ?></td>
        <td><?php echo osc_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
        <td class="main"><?php echo $confirmation['fields'][$i]['field']; ?></td>
      </tr>

<?php
        }
      }
?>

    </table>
  </div>

<?php
    }
  }

  if ( $OSCOM_Order->hasInfo('comments') ) {
?>

  <h2><?php echo '<strong>' . HEADING_ORDER_COMMENTS . '</strong> <a href="' . osc_href_link('checkout', 'payment', 'SSL') . '"><span class="badge badge-warning">' . TEXT_EDIT . '</span></a>'; ?></h2>

  <div class="contentText">
    <?php echo nl2br(osc_output_string_protected($OSCOM_Order->getInfo('comments'))); ?>
  </div>

<?php
  }
?>

  <div class="contentText">
    <div style="float: left; width: 60%; padding-top: 5px; padding-left: 15%;">
      <div class="progress">
        <div class="bar" style="width: 100%;"></div>
      </div>

      <table border="0" width="100%" cellspacing="0" cellpadding="2">
        <tr>
          <td align="center" width="33%" class="checkoutBarFrom"><?php echo '<a href="' . osc_href_link('checkout', 'shipping', 'SSL') . '" class="checkoutBarFrom">' . CHECKOUT_BAR_DELIVERY . '</a>'; ?></td>
          <td align="center" width="33%" class="checkoutBarFrom"><?php echo '<a href="' . osc_href_link('checkout', 'payment', 'SSL') . '" class="checkoutBarFrom">' . CHECKOUT_BAR_PAYMENT . '</a>'; ?></td>
          <td align="center" width="33%" class="checkoutBarCurrent"><?php echo CHECKOUT_BAR_CONFIRMATION; ?></td>
        </tr>
      </table>
    </div>

    <div style="float: right;">

<?php
  echo $OSCOM_Payment->process_button();

  echo osc_draw_button(IMAGE_BUTTON_CONFIRM_ORDER, 'check', null, 'success');
?>

    </div>
  </div>

</div>

</form>
