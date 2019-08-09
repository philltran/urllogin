<?php

namespace Drupal\urllogin\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Html;

/**
 * Controller routines for urllogin routes.
 *
 * @todo Add option to export csv with First Name and Last Name fields
 */
class UrlloginController extends ControllerBase {

  /**
   * Diagnostic test page for setting up urllogin urls.
   *
   * @param string $urlstring
   *   login string from URL.
   * @param string $dest
   *   Optional destination URL.
   *
   * @return object
   *   Page containing test results
   */
  public function linkTest($urlstring = 'none', $dest = '') {
    // module_load_include('inc', 'urllogin', 'urllogin');.
    module_load_include('inc', 'urllogin', 'urllogin_security');
    // Sanitize.
    $urlstr = Html::escape($urlstring);
    $element = [
      '#markup' => '',
    ];

    $page = "<ul><li>Initial URL string = [$urlstr]</li>";
    $resultmsg = "";
    $user = User::load(\Drupal::currentUser()->id());
    $config = $this->config('urllogin.settings');
    $codekey = $config->get('urllogin.codekey');
    $codemin = $config->get('urllogin.codemin');
    $uid = urllogin_decode($urlstr, $codekey, $codemin, urllogin_passphrase(),
      $resultmsg, $user->get('uid')->value
    );
    if ($uid > -1) {
      $account = urllogin_testuid($uid, $resultmsg);
    }
    else {
      $account = NULL;
    }
    if ($account != NULL) {
      // Find where to go: get rid of first two arguments and use the rest of
      // the URL as the destination.
      $current_path = \Drupal::service('path.current')->getPath();
      $args = explode('/', $current_path);
      unset($args[0]);
      unset($args[1]);
      unset($args[2]);
      $goto = implode('/', $args);

      // Maintain the original query string.
      $query = $_GET;
      unset($query['q']);
      if (count($query) > 0) {
        $goto .= '?' . implode('&', $query);
      }

      // Check in case this user is already logged in.
      $logged_in = ($user->get('uid')->value == $uid);
      if ($logged_in) {
        $resultmsg = t('User %username (%uid) was already logged in. Redirected to: %goto',
          [
            '%username' => $account->get('name')->value,
            '%uid' => $uid,
            '%goto' => $goto,
          ]);
        \Drupal::logger('urllogin')->notice($resultmsg);
      }
      else {
        $resultmsg = t('Logging in as %username (%uid). Redirected to: %goto',
          [
            '%username' => $account->get('name')->value,
            '%uid' => $uid,
            '%goto' => $goto,
          ]);
      }
      // Get rid of first two arguments and use the rest of the URL as the
      // destination.
      $page .= "<li>$resultmsg</li><li>goto: $goto</li></ul>";
    }
    $element['#markup'] .= $page;
    return $element;
  }

  /**
   * Displays status page.
   *
   * Displays the status page that and allows a URL string to be generated for
   * test purposes.
   *
   * @param int $testuid
   *   Optional UID for generating a test URL login string.
   *
   * @return array
   *   Page containing test results
   */
  public function status($testuid = 0) {
    module_load_include('inc', 'urllogin', 'urllogin_security');
    $element = [
      '#markup' => '',
    ];
    // Load config.
    $config = $this->config('urllogin.settings');
    $codekey = $config->get('urllogin.codekey');
    $codemin = $config->get('urllogin.codemin');
    // This will sanitize it as well.
    $uid = (int) $testuid;
    $passphrase = urllogin_passphrase();
    $page = '<ul>';
    $page .= t('<li>Test UID: @uid </li>', ['@uid' => $uid]);
    $page .= t('<li>Passphrase: @passphrase</li>', ['@passphrase' => $passphrase]);
    $page .= t('<li>Current Validation number: @codekey .</li>', ['@codekey' => $codekey]);
    $page .= t('<li>Minimum validation number: @codemin .</li>', ['@codemin' => $codemin]);
    $urlstr = urllogin_encode($uid, $codekey, urllogin_passphrase());
    $route_parameters = ['urlstring' => $urlstr];
    $page .= '<li>' . t('Encoded URL access string: [') . $urlstr . ']</li>';
    $page .= '</ul>';
    $testlink = 'l_test/' . $urlstr;
    $testlink = Link::fromTextAndUrl($testlink, Url::fromRoute('urllogin.l_test', $route_parameters))
      ->toString();
    $testpage = Link::fromTextAndUrl(t('the test page'), Url::fromRoute('urllogin.l_test'))
      ->toString();
    $page .= t('<p>This page can be used to generate individual access strings for testing purposes.
    Simply add the UID of the user to the end of the url for this page, revisit the page and the
    access string will be displayed above.</p> <p>To test the access string, 
    use @testpage by appending the access string to it, e.g.: @testlink.</p>',
      [
        '@testpage' => $testpage,
        '@testlink' => $testlink,
      ]);

    $element['#markup'] .= $page;

    return $element;
  }

  /**
   * Link to download of user access URL's as a csv.
   *
   * A theme template file is needed of the page-urllogin-userlist.csv.tpl
   * containing the single line: < ?php print $content; ? >.
   *
   * @todo test whether profile module is installed and if fields are correct
   * @todo look at integrating with content profile module
   *
   * @return array
   *   Page containing user access URL's as a file of tab separated variables
   */
  public function userList() {
    module_load_include('inc', 'urllogin', 'urllogin_security');
    $codekey = \Drupal::config('urllogin.settings')->get('codekey');
    $passphrase = urllogin_passphrase();
    $thissite = \Drupal::request()->getSchemeAndHttpHost();
    $destination = '/' . \Drupal::config('urllogin.settings')
      ->get('destination');
    $output = "UID, Username, Email, Login URL \n";
    $response = new Response();
    // Tell browser this is not a web page but a file to download.
    $response->headers->set('Content-type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'inline; filename="userlist.csv"');

    // Load user object for active accounts.
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->execute();
    $users = User::loadMultiple($ids);
    // Generate each row in CSV file.
    foreach ($users as $data) {
      // Check if user has permission to login via url.
      if ($data->hasPermission('login via url')) {
        // Create login url.
        $urlstr = $thissite . '/l/' . urllogin_encode($data->uid->value, $codekey, $passphrase) . $destination;
        $output .= $data->uid->value . "," . $data->name->value . "," . $data->mail->value . "," . $urlstr . "\r\n";

      }
    }

    $response->setContent(render($output));
    return $response;
  }

  /**
   * Returns a render-able array for a test page.
   */
  public function content() {
    $build = [
      '#markup' => $this->t('Hello World!'),
    ];
    return $build;
  }

  /**
   * This is the function that actually performs the login.
   *
   * @param string $urlstring
   *   login string from URL.
   * @param string $arg
   *
   *   The function first validates the URL login string.
   *   If good, then the user is logged in and transferred to the destination
   *   page. Otherwise they are taken to the front page. Results, good or bad,
   *   are logged with watchdog. If the intended user is already logged in,
   *   then redirect will occur even if link is outdated.
   */
  public function login($urlstring = 'none', $arg = NULL) {
    module_load_include('inc', 'urllogin', 'urllogin_security');
    // Sanitize.
    $urlstr = Html::escape($urlstring);
    $resultmsg = "";
    $user = User::load(\Drupal::currentUser()->id());
    $config = $this->config('urllogin.settings');
    $codekey = $config->get('codekey');
    $codemin = $config->get('codemin');
    $uid = urllogin_decode($urlstr, $codekey, $codemin, urllogin_passphrase(),
      $resultmsg, $user->get('uid')->value
    );
    if ($uid > -1) {
      $account = urllogin_testuid($uid, $resultmsg);
    }
    else {
      $account = NULL;
    }
    \Drupal::logger('urllogin')->debug($resultmsg);
    if ($account != NULL) {
      // Find where to go: get rid of first two arguments and use the rest of
      // the URL as the destination.
      $current_path = \Drupal::service('path.current')->getPath();
      $args = explode('/', $current_path);
      unset($args[0]);
      unset($args[1]);
      unset($args[2]);
      $goto = implode('/', $args);

      // Maintain the original query string.
      $query = $_GET;
      unset($query['q']);
      if (count($query) > 0) {
        $goto .= '?' . implode('&', $query);
      }

      // Check in case this user is already logged in.
      $logged_in = ($user->get('uid')->value == $uid);
      if ($logged_in) {
        $resultmsg = t('User %username (%uid) was already logged in. Redirected to: %goto',
          ['%username' => $account->name, '%uid' => $uid, '%goto' => $goto]);
        \Drupal::logger('urllogin')->notice($resultmsg);
      }
      // Log the user in.
      else {
        $account = User::load($uid);
        // Log in user. This function called by user_login_submit() which does
        // stuff that is not needed.
        user_login_finalize($account);
        $user = User::load(\Drupal::currentUser()->id());
        $logged_in = ($user->get('uid')->value == $uid);
        if ($logged_in) {
          $resultmsg = t('Logging in as %username (%uid). Redirected to: %goto',
            ['%username' => $account->name, '%uid' => $uid, '%goto' => $goto]);
          \Drupal::logger('urllogin')->notice($resultmsg);
          // If persistent_login is installed, then set "remember me".
          if (\Drupal::moduleHandler()->moduleExists('persistent_login')) {
            _persistent_login_create_cookie($account);
          }
        }
        else {
          $resultmsg = t('Failed login as %username (%uid)',
            ['%username' => $account->name, '%uid' => $uid]);
        }
      }
      if ($logged_in) {
        $url = '/';
        $url .= implode('/', $args);
        $redirect = new RedirectResponse(Url::fromUserInput($url)->toString());
        $redirect->send();
      }
    }
    // Logs a notice.
    \Drupal::logger('urllogin')->notice($resultmsg);
    if ($uid == -2) {
      $response = [
        '#markup' => '<h1>' . t('The link you used to access this page has expired.') . '</h1>' .
        '<p>' . t('If you have created a password, you can log on') . ' ' . Link::fromTextAndUrl(t('here'), Url::fromRoute('user.login'))
          ->toString() . '.</p>',
      ];
      return $response;
    }
    else {
      return $this->redirect('<front>');
    }
  }

}
