<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Http\Repository\NagadRepository;

class NagadController extends Controller
{
    protected $nagad_repository;
    protected $merchant_id;
    protected $private_key;
    protected $public_key;
    protected $pament_ref_id;
    protected $challange;
    protected $account_number;
    protected $callback_url;
    protected  $nagadHost;

    public function __construct(NagadRepository $nagad_repository)
    {
        $this->nagad_repository = $nagad_repository;
        $this->merchant_id = "";
        $this->private_key = "";
        $this->public_key = "";
        $this->account_number = "";
        $this->nagadHost = "";
    }

    /**
     * Reseller checkout with nagad payment gateway
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentWithNagad(Request $request)
    {
        try {
            $DateTime = Date('YmdHis');
            $invoiceNo = date('dmyhis');
            $this->callback_url = 'http://127.0.0.1:8000/nagad-payment-varify';
            $SensitiveData = [
                'merchantId' => $this->merchant_id,
                'datetime' => $DateTime,
                'orderId' => $invoiceNo,
                'challenge' => $this->nagad_repository->generateRandomString()
            ];
            $PostData = array(
                'accountNumber' => $this->account_number,
                'dateTime' => $DateTime,
                'sensitiveData' =>  $this->nagad_repository->EncryptDataWithPublicKey(json_encode($SensitiveData), $this->public_key),
                'signature' =>  $this->nagad_repository->SignatureGenerate(json_encode($SensitiveData), $this->private_key)
            );
            $initializeUrl = $this->nagadHost . "/check-out/initialize/" . $this->merchant_id . "/" . $invoiceNo;
            $Result_Data = $this->nagad_repository->HttpPostMethod($initializeUrl, $PostData);
            if (isset($Result_Data['sensitiveData']) && isset($Result_Data['signature'])) {
                if ($Result_Data['sensitiveData'] != "" && $Result_Data['signature'] != "") {

                    $PlainResponse = json_decode($this->nagad_repository->DecryptDataWithPrivateKey($Result_Data['sensitiveData'], $this->private_key), true);
                    if (isset($PlainResponse['paymentReferenceId']) && isset($PlainResponse['challenge'])) {

                        $paymentReferenceId = $PlainResponse['paymentReferenceId'];
                        $challenge = $PlainResponse['challenge'];

                        $SensitiveDataOrder = array(
                            'merchantId' => $this->merchant_id,
                            'orderId' => $invoiceNo,
                            'currencyCode' => '050',
                            'amount' => $request->amount,
                            'amount' => 10,
                            'challenge' => $challenge
                        );

                        $merchantAdditionalInfo = '{}';

                        $PostDataOrder = array(
                            'sensitiveData' =>  $this->nagad_repository->EncryptDataWithPublicKey(json_encode($SensitiveDataOrder), $this->public_key),
                            'signature' =>  $this->nagad_repository->SignatureGenerate(json_encode($SensitiveDataOrder), $this->private_key),
                            'merchantCallbackURL' => $this->callback_url,
                            'additionalMerchantInfo' => (object)$merchantAdditionalInfo
                        );
                        $OrderSubmitUrl = $this->nagadHost . "/check-out/complete/" . $paymentReferenceId;
                        $Result_Data_Order = $this->nagad_repository->HttpPostMethod($OrderSubmitUrl, $PostDataOrder);
                        if ($Result_Data_Order['status'] == "Success") {
                            $callBackUrl = ($Result_Data_Order['callBackUrl']);
                            return new RedirectResponse($callBackUrl);
                        } else {
                            return 'payment failed 1';
                        }
                    } else {
                        return 'payment failed 2';
                    }
                } else {
                    return 'payment failed 3';
                }
            } else {
                return $Result_Data;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Varify nagad payment
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function varifyNagadPayment(Request $request)
    {
        try {
            $url = $this->nagadHost . "/verify/payment/" . $request->payment_ref_id;
            $json = $this->nagad_repository->HttpGetMethod($url);
            $result = json_decode($json, true);
            if (isset($result['status']) && $result['status'] == 'Success') {
                return redirect()->route('nagad.success');
            } else {
                return "invaild payment";
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    /**
     * Redire nagad payment success page
     * 
     * @return mixed
     */
    public function nagadSuccess()
    {
        return 'payment success';
    }
}
