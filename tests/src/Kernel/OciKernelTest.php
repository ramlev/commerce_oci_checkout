<?php

namespace Drupal\Tests\commerce_oci_checkout\Kernel;

use Drupal\Tests\commerce_cart\Kernel\CartKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Kernel tests for this module.
 *
 * @group commerce_oci_checkout
 */
class OciKernelTest extends CartKernelTestBase {

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * Variation1.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation1;

  /**
   * Variation2.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation2;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_oci_checkout',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    $product_storage = $this->entityTypeManager->getStorage('commerce_product');
    $product = $product_storage->create([
      'type' => 'default',
      'title' => 'product',
    ]);
    $product->save();

    $variation1 = $variation_storage->create([
      'product_id' => $product,
      'type' => 'default',
      'title' => 'Variation 1',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '39.99',
        'currency_code' => 'USD',
      ],
    ]);
    $variation1->save();
    $variation2 = $variation_storage->create([
      'product_id' => $product,
      'type' => 'default',
      'title' => 'Variation 2',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '22.33',
        'currency_code' => 'USD',
      ],
    ]);
    $variation2->save();

    $this->variation1 = $variation1;
    $this->variation2 = $variation2;
  }

  /**
   * Test that multiple products are actually possible to add.
   *
   * @see https://www.drupal.org/project/commerce_oci_checkout/issues/3198970
   */
  public function testMultipleProducts() {
    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
    $current_user = $this->container->get('current_user');
    // Create 2 users, just so we do not fail the user 1 check. Facepalm.
    /** @var \Drupal\user\UserInterface $account */
    $account = User::create([
      'name' => 'user1@example.com',
      'mail' => 'user1@example.com',
    ]);
    $account->save();
    $account = User::create([
      'name' => 'testuser@example.com',
      'mail' => 'testuser@example.com',
    ]);
    $account->save();
    $current_user->setAccount($account);
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::create(['label' => 'OCI role', 'id' => 'oci_role']);
    $role->grantPermission('use commerce_oci_checkout');
    $role->save();
    $account->addRole($role->id());
    $account->save();
    /** @var \Drupal\commerce_oci_checkout\CartProvider $cart_provider */
    $cart_provider = $this->container->get('commerce_oci_checkout.cart_provider');
    $cart = $cart_provider->createCart('default');
    self::assertNotNull($cart_provider->getCartId('default'));
    self::assertNotFalse($cart_provider->getCartId('default'));
    /** @var \Drupal\commerce_cart\CartManager $cart_manager */
    $cart_manager = $this->container->get('commerce_cart.cart_manager');
    $cart_manager->addEntity($cart, $this->variation1);
    self::assertNotNull($cart_provider->getCartId('default'));
    self::assertNotFalse($cart_provider->getCartId('default'));
    $cart = $cart_provider->getCart('default');
    $cart_manager->addEntity($cart, $this->variation2);
    self::assertNotNull($cart_provider->getCartId('default'));
    self::assertNotFalse($cart_provider->getCartId('default'));
    $cart = $cart_provider->getCart('default');
    self::assertCount(2, $cart->getItems());
  }

}
