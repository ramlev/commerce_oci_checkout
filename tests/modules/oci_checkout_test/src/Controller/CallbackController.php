<?php

namespace Drupal\oci_checkout_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CallbackController.
 */
class CallbackController extends ControllerBase {

  /**
   * Callback for the test callback.
   */
  public function callback(Request $request) {
    $content = $request->request->all();
    return new JsonResponse($content);
  }

}
