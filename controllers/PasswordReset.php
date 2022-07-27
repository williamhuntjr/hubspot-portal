<?php
namespace Portal\Controller\PasswordReset;

class Controller
{
  private $model;

  public function __construct($model) {
    global $portal;

    $this->model = $model;

    if ($this->model->data['method'] == "POST") {
      if (isset($_POST['emailAddress']) && !empty($_POST['emailAddress'])) {

        if ($portal->is_email_registered($_POST['emailAddress'])) {
          if ($this->reset_password($_POST['emailAddress'])) {
            $this->model->data['success'] = "true";
          }
          else {
            $this->model->data['error'] = "true";
            $this->model->data['error_message'] = "There was an issue with resetting your password. Please contact support directly.";
          }
        }
        else {
          $this->model->data['error'] = "true";
          $this->model->data['error_message'] = "The specified e-mail address does not have an account. Please return to the login page and try registering it first.";
        }
      }
    }
    if ($this->model->data['method'] == "GET" && $this->model->data['action'] == "confirm") {
      if ($portal->check_password_token($this->model->data['token'])) { $this->model->data['valid_token'] = "true"; }
      else { $this->model->data['valid_token'] = "false"; }
    }
  }

  function reset_password($email) {
    global $portal;

    // Generate a random activation key
    $token="";

    // Establish which characters to use inside of activation key
    $chars = "subinsblogabcdefghijklmanopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    // Determine length of the key
    $size = strlen($chars);

    // Loop through and generate random values to build the key
    for($i = 0;$i < 32;$i++) {
      $token .= $chars[rand(0,$size-1)];
    }

    // Query to activate account by updating activation column
    try {
      $portal->db->connect();
      // Prepare SQL query for sanitization
      $stmt = $portal->db->pdo->prepare("UPDATE accounts SET resetting_password='1' WHERE email=:email");

      // Execute query
      $stmt->execute(['email' => $email]);

      // Prepare SQL query for sanitization
      $stmt = $portal->db->pdo->prepare("UPDATE accounts SET reset_token=:token WHERE email=:email");

      // Execute query
      $stmt->execute(['token' => $token, 'email' => $email]);
    }

    // Store any errors in the error log
    catch(PDOException $e) {
      error_log($e->getMessage(),0);

      $this->model->data['error'] = "true";
      $this->model->data['error_message'] = "There was an error submitting your request. Please contact our support for more assistance.";

      throw $e;
    }


    // Send activation email
    $message = '
    <!doctype html>
    <html>
      <head>
        <meta name="viewport" content="width=device-width">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Simple Transactional Email</title>
        <style>
        /* -------------------------------------
            INLINED WITH htmlemail.io/inline
        ------------------------------------- */
        /* -------------------------------------
            RESPONSIVE AND MOBILE FRIENDLY STYLES
        ------------------------------------- */
        @media only screen and (max-width: 620px) {
          table[class=body] h1 {
            font-size: 28px !important;
            margin-bottom: 10px !important;
          }
          table[class=body] p,
                table[class=body] ul,
                table[class=body] ol,
                table[class=body] td,
                table[class=body] span,
                table[class=body] a {
            font-size: 16px !important;
          }
          table[class=body] .wrapper,
                table[class=body] .article {
            padding: 10px !important;
          }
          table[class=body] .content {
            padding: 0 !important;
          }
          table[class=body] .container {
            padding: 0 !important;
            width: 100% !important;
          }
          table[class=body] .main {
            border-left-width: 0 !important;
            border-radius: 0 !important;
            border-right-width: 0 !important;
          }
          table[class=body] .btn table {
            width: 100% !important;
          }
          table[class=body] .btn a {
            width: 100% !important;
          }
          table[class=body] .img-responsive {
            height: auto !important;
            max-width: 100% !important;
            width: auto !important;
          }
        }
        /* -------------------------------------
            PRESERVE THESE STYLES IN THE HEAD
        ------------------------------------- */
        @media all {
          .ExternalClass {
            width: 100%;
          }
          .ExternalClass,
                .ExternalClass p,
                .ExternalClass span,
                .ExternalClass font,
                .ExternalClass td,
                .ExternalClass div {
            line-height: 100%;
          }
          .apple-link a {
            color: inherit !important;
            font-family: inherit !important;
            font-size: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
            text-decoration: none !important;
          }
          #MessageViewBody a {
            color: inherit;
            text-decoration: none;
            font-size: inherit;
            font-family: inherit;
            font-weight: inherit;
            line-height: inherit;
          }
          .btn-primary table td:hover {
            background-color: #34495e !important;
          }
          .btn-primary a:hover {
            background-color: #34495e !important;
            border-color: #34495e !important;
          }
        }
        </style>
      </head>
      <body class="" style="background-color: #f6f6f6; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
        <table border="0" cellpadding="0" cellspacing="0" class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background-color: #f6f6f6;">
          <tr>
            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
            <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; Margin: 0 auto; max-width: 580px; padding: 10px; width: 580px;">
              <div class="content" style="box-sizing: border-box; display: block; Margin: 0 auto; max-width: 580px; padding: 10px;">
                <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                  <tr>
                    <td><img src="' . $portal->site_url . '/assets/images/logo.png" alt="Logo" style="width: 120px"></td>
                  </tr>
                </table>
                <!-- START CENTERED WHITE CONTAINER -->
                <span class="preheader" style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;">This is preheader text. Some clients will show this text as a preview.</span>
                <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background: #ffffff; border-radius: 3px;">
                  <!-- START MAIN CONTENT AREA -->
                  <tr>
                    <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                      <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                        <tr>
                          <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                            <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">Hi,</p>
                            <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">You have recently requested to reset your Customer Portal password.  Please click the button below to continue.</p>
                            <table border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; box-sizing: border-box;">
                              <tbody>
                                <tr>
                                  <td align="left" style="font-family: sans-serif; font-size: 14px; vertical-align: top; padding-bottom: 15px;">
                                    <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: auto;">
                                      <tbody>
                                        <tr>
                                          <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; background-color: #3498db; border-radius: 5px; text-align: center;"> <a href="' . $portal->site_url . '/reset-password/confirm/' . $token .'" target="_blank" style="display: inline-block; color: #ffffff; background-color: #3498db; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; margin: 0; padding: 12px 25px; text-transform: capitalize; border-color: #3498db;">Reset Password</a> </td>
                                        </tr>
                                      </tbody>
                                    </table>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">If you have any issues resetting your password, please <a href="' . $portal->site_url . '/contact-us">contact your Customer Success Team</a> with your concerns.</p>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>

                <!-- END MAIN CONTENT AREA -->
                </table>

              <!-- END CENTERED WHITE CONTAINER -->
              </div>
            </td>
            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
          </tr>
        </table>
      </body>
    </html>
    ';

    $email_data = array(
      'to' => $email,
      'subject' => 'Your Customer Portal password reset link',
      'message' => $message,
      'alt' => 'You recently requested to reset your password.  You can do so at the following URL: ' . $portal->site_url . '/reset-password/token/' . $token
    );
    $portal->email($email_data['to'], $email_data['subject'], $email_data['message'], $email_data['alt']);
    $this->model->data['action'] = "activation_sent";

    return true;
  }

}

?>
