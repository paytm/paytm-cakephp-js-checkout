# Paytm Payment Plugin for CakePHP

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require paytm/payment
```

## Configuring
**Note: For CakePHP 4.x and above auto-discovery takes care of below configuration.**

When composer installs CakePHP Paytm library successfully, 
register the `Paytm\JsCheckout\PaytmServiceProvider` in your `config/app.php` configuration file.

```php
// Paytm configuration settings...
    'Paytm' => [
        'merchantKey' => env('MERCHANT_KEY',''),
        'merchantId' => env('MERCHANT_ID',''),
        'enviroment' => env('PAYTM_ENVIRONMENT',''),
        'callbackUrl' => env('PAYTM_CALLBACK_URL',''),
    ],
```
OR

#### Add the paytm credentials to the `.env` file
```bash
export PAYTM_ENVIRONMENT=staging
export PAYTM_MERCHANT_ID=YOUR_MERCHANT_ID_HERE
export PAYTM_MERCHANT_KEY =YOUR_SECRET_KEY_HERE
export PAYTM_CALLBACK_URL =YOUR_CALLBACK_URL
```

Note : All the credentials mentioned are provided by Paytm after signing up as merchant.

## Usage


### Making a transaction
```
<?php 
// src/Controller/PaytmController.php
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\ORM\TableRegistry;
use paytm\payment\Paytm;

class PaytmController extends Controller
{

    // display a form for payment
    public function initiate()
    {
        $this->render('paytm');
    }

    /* Make payment */
    public function payment(){
        
        if ($this->request->is('post')) {
        $postData = $this->request->getData();
        $amount = 1; //Amount to be paid
        $order_id = time();
        $enviroment = env('PAYTM_ENVIRONMENT');
        $userData = [
            "mid"  => env('PAYTM_MERCHANT_ID'), //mandatory
            "websiteName"   => (strpos($enviroment, "stage") == true) ? "WEBSTAGING" : "DEFAULT", //mandatory
            "callbackUrl"   => env('PAYTM_CALLBACK_URL'), //mandatory
            'name' => $postData['name'], // Name of user
            'mobile' => $postData['mobile'], //Mobile number of user
            'email' => $postData['email'], //Email of user
            'txnAmount' => $amount, //mandatory
            'orderId' => $order_id //mandatory
        ];
        $Paytm = new Paytm();
        $txntoken = $Paytm->initiatePayment($userData);
        if(!empty($txntoken)){
            $data = array('success'=> true,'txnToken' => $txntoken, 'txnAmount' => $amount, 'orderId' =>$order_id );
            $jsonData = json_encode($data);
        
        }else{
            //echo json_encode(array('success'=> false,'txnToken' => '','data'=>$res));
            $data = array('success'=> false,'txnToken' => '', 'txnAmount' => '', 'orderId' => '' );
            $jsonData = json_encode($data);
        }

        $this->response = $this->response->withType('application/json');
        // Set the JSON data as the response body
        $this->response = $this->response->withStringBody($jsonData);
        // Return the response
        return $this->response;

        }

    }

    /*Paytm Callback */
    public function paymentCallback()
    {
        if(!empty($_POST['CHECKSUMHASH'])){
            $post_checksum = $_POST['CHECKSUMHASH'];
        }else{
            $post_checksum = "";
        }

        if(! empty($_POST) && isset($_POST['STATUS'])){
             // Access the POST data from the request
            $callbackData = $this->request->getData();
            $Paytm = new Paytm();
            $requestData = $Paytm->verifyPaymentResponse($_POST);
            if(isset($requestData['body']) && $requestData['body']['resultInfo']['resultStatus'] == 'TXN_SUCCESS'){
                $msg = "Thank you for your order. Your transaction has been successful.";
                $this->set(compact('msg'));
                $this->render('success');
            }else{
                $msg = "Thank You. However, the transaction has been Failed For Reason: " . $requestData['body']['resultInfo']['resultMsg'];
                $this->set(compact('msg'));
                $this->render('error');
            }
            
        }
    }
}
?>

```
### Making a view page
```
<!-- File: templates/Paytm/paytm.php -->
<?= $this->Html->css('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css') ?>

<div id="paytm-pg-spinner" class="paytm-pg-loader" style="display: none;">
  <div class="bounce1"></div>
  <div class="bounce2"></div>
  <div class="bounce3"></div>
  <div class="bounce4"></div>
  <div class="bounce5"></div>
</div>
<div class="paytm-overlay" style="display:none;"></div>
<div class="container" width="500px">
    <div class="panel panel-primary" style="margin-top:110px;">
        <div class="panel-heading"><h3 class="text-center">Payment gateway using Paytm Cakephp JS Checkout</h3></div>
        <div class="panel-body">
            <?= $this->Form->create(null, ['url' => ['controller' => 'Paytm', 'action' => '/payment']]) ?>
                <div class="row">
                    <div class="col-md-12">
                        <strong>Name:</strong>
                        <?= $this->Form->text('name', ['class' => 'form-control name', 'placeholder' => 'Name', 'required' => true]) ?>
                    </div>
                    <div class="col-md-12">
                        <strong>Mobile No:</strong>
                        <?= $this->Form->text('mobile', ['class' => 'form-control mobile', 'maxlength' => '10', 'placeholder' => 'Mobile No.', 'required' => true]) ?>
                    </div>
                    <div class="col-md-12">
                        <strong>Email:</strong>
                        <?= $this->Form->email('email', ['class' => 'form-control email', 'placeholder' => 'Email', 'required' => true]) ?>
                    </div>
                    <div class="col-md-12" >
                        <br/>
                        <div class="btn btn-info">
                            Term Fee : 1 Rs/-
                        </div>
                    </div>
                    <div class="col-md-12">
                        <br/>
                        <?= $this->Form->button('Paytm', ['type' => 'submit', 'class' => 'btn btn-success pay']) ?>
                    </div>
                </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<?= $this->Html->script('https://code.jquery.com/jquery-2.2.4.min.js', ['integrity' => 'sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=', 'crossorigin' => 'anonymous']) ?>

<?php if(env('PAYTM_ENVIRONMENT')=='production'){?>
        <script type="application/javascript" crossorigin="anonymous" src="https:\\securegw.paytm.in\merchantpgpui\checkoutjs\merchants\<?php echo env('PAYTM_MERCHANT_ID')?>.js" ></script>
    <?php }else{ ?>
       <script type="application/javascript" crossorigin="anonymous" src="https:\\securegw-stage.paytm.in\merchantpgpui\checkoutjs\merchants\<?php echo env('PAYTM_MERCHANT_ID')?>.js" ></script>
    <?php }  ?>

<script type="text/javascript">
    $(".pay").click(function(e) {
        var name = $('.name').val();
        var mobile = $('.mobile').val();
        var email = $('.email').val();
        if (name === "" || mobile === "" || email === "") {
            alert("Please fill all the fields");
            return false;
        }
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: '<?= $this->Url->build(['controller' => 'Paytm', 'action' => '/payment']) ?>',
            data: {
                'name': $('.name').val(),
                'mobile': $('.mobile').val(),
                'email': $('.email').val(),
            },
         	headers: {
                    'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>',
                },
            success: function(data) {
            	console.log(data);
                $('.paytm-pg-loader').show();
                $('.paytm-overlay').show();
                if (data.txnToken === "") {
                    alert(data.message);
                    $('.paytm-pg-loader').hide();
                    $('.paytm-overlay').hide();
                    return false;
                }
                var token = '<?= $this->request->getAttribute('csrfToken') ?>';
                console.log(data.orderId);
                console.log(data.txnToken);
                console.log(data.txnamount);
                invokeBlinkCheckoutPopup(data.orderId, data.txnToken, data.txnamount,token);
            }
        });

    });

    function invokeBlinkCheckoutPopup(orderId, txnToken, amount,token) {
    	console.log('invokeBlinkCheckoutPopup');
        window.Paytm.CheckoutJS.init({
            "root": "",
            "flow": "DEFAULT",
            "data": {
                "orderId": orderId,
                "token": txnToken,
                "tokenType": "TXN_TOKEN",
                "amount": amount,
            },
            headers: {
                    'X-CSRF-Token': token,
                },
            handler: {
                transactionStatus: function(data) {
                },
                notifyMerchant: function notifyMerchant(eventName, data) {
                    if (eventName === "APP_CLOSED") {
                        $('.paytm-pg-loader').hide();
                        $('.paytm-overlay').hide();
                    }
                    console.log("notify merchant about the payment state");
                }
            }
        }).then(function() {
            window.Paytm.CheckoutJS.invoke();
        });
    }
</script>
```
### Making a sucess page
```
<!-- File: templates/Paytm/success.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Order Success</title>
    <!-- Add any CSS or other head content here -->
</head>
<body>
    <h1>Thank You!!!</h1>
    <p style="color:green"><?php echo $msg;?></p>
    <!-- Add any other content as needed -->
</body>
</html>
```
### Making a error page
```
<!-- File: templates/Paytm/error.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Order Error</title>
    <!-- Add any CSS or other head content here -->
</head>
<body>
    <h1>Sorry!!!</h1>
    <p style="color:red"><?php echo $msg;?></p>
    <!-- Add any other content as needed -->
</body>
</html>

```
### Define routes
```
Router::connect('/paytm/initiate', ['controller' => 'Paytm', 'action' => 'initiate']);
Router::connect('/paytm/payment', ['controller' => 'Paytm', 'action' => 'payment']);
Router::connect('/paytm/paymentCallback', ['controller' => 'Paytm', 'action' => 'paymentCallback'])->setMethods(['POST']);

```
Important: The `callback_url` must not be csrf protected [Check out here to how to do that](https://ao-system.net/en/note/107)
```
### Write code in src/Application.php
```
```
public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue{

    $csrf = new CsrfProtectionMiddleware(['httponly'=>true]);
    $csrf->skipCheckCallback(function($request){
    $path = $request->getUri()->getPath();
       if($path == '/paytm/paymentCallback'){
                return true;
            }
            
        });
    $middlewareQueue
    // Catch any exceptions in the lower layers,
    // and make an error page/response
    ->add(new ErrorHandlerMiddleware(Configure::read('Error')))

    // Handle plugin/theme assets like CakePHP normally does.
    ->add(new AssetMiddleware([
        'cacheTime' => Configure::read('Asset.cacheTime'),
        ]))

    // Add routing middleware.
    // If you have a large number of routes connected, turning on routes
    // caching in production could improve performance. For that when
    // creating the middleware instance specify the cache config name by
    // using it's second constructor argument:
    // `new RoutingMiddleware($this, '_cake_routes_')`
    ->add(new RoutingMiddleware($this))

    // Parse various types of encoded request bodies so that they are
    // available as array through $request->getData()
    // https://book.cakephp.org/4/en/controllers/middleware.html#body-parser-middleware
    ->add(new BodyParserMiddleware())

    ->add($csrf);
    return $middlewareQueue;
  }
```
** Add autoload file in composer.json file in your project directory
```
 "autoload": {
        "psr-4": {
            "App\\": "src/",
            "paytm\\payment\\":"vendor/paytm/payment/src"
            
        }
    },



```