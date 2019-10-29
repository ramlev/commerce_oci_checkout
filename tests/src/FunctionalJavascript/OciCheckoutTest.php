<?php

namespace Drupal\Tests\commerce_oci_checkout\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * The full test over here.
 *
 * @group commerce_oci_checkout
 */
class OciCheckoutTest extends CommerceWebDriverTestBase {

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_oci_checkout',
    'oci_checkout_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '39.99',
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);
    $this->drupalLogout();
    // Now log in using OCI start instead.
    $this->drupalGet(Url::fromRoute('commerce_oci_checkout.session_start', [], [
      'query' => [
        'hook_url' => Url::fromRoute('oci_checkout_test.callback', [], [
          'absolute' => TRUE,
        ])->toString(),
        'username' => $this->adminUser->getEmail(),
        'password' => $this->adminUser->pass_raw,
      ],
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getAdministratorPermissions() {
    $permissions = parent::getAdministratorPermissions();
    $permissions[] = 'use commerce_oci_checkout';
    return $permissions;
  }

  /**
   * Confirm we can log in, and check out using the external system.
   */
  public function testCheckout() {
    // First create a product.
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    // And just to test that, let's just add another one, yeah?
    $this->submitForm([], 'Add to cart');
    // Go to the cart, we should be redirected.
    $this->drupalGet('cart');
    // Make sure we are now on the right cart page.
    $this->assertSession()->addressEquals('oci-cart');
    // Click through to the fake procurement system.
    $this->submitForm([], 'Go to procurement system');
    // Now we should look at a page containing JSON. Verify that certain values
    // are set to what we are expecting.
    $contents = $this->getSession()->getPage()->getText();
    $json = json_decode($contents);
    // Should not decode to false.
    $this->assertTrue((bool) $json);
    // Should be 2 items of this SKU.
    $this->assertEqual($json->{"NEW_ITEM-QUANTITY"}->{"1"}, "2.00");
    $this->assertEqual($json->{"NEW_ITEM-PRICE"}->{"1"}, '39.990000');
  }

}
