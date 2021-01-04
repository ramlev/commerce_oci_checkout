<?php

namespace Drupal\oci_checkout_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller to return all of the content in the OCI request.
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
