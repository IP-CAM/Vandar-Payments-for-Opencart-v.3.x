<?php



class ControllerExtensionPaymentVandar extends Controller
{
    public function index()
    {

        $this->load->language('extension/payment/vandar');
        $this->load->model('checkout/order');
        $this->load->library('encryption');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        //$encryption = new Encryption($this->config->get('config_encryption'));

        //if ($this->currency->getCode() != 'RLS') {
        //
        //    $this->currency->set('RLS');
        //}

        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['error_warning'] = false;

        $telephone   = $order_info['telephone'];
        $description = 'پرداخت سفارش شناسه ' . $order_info['order_id'];

        if (extension_loaded('curl')) {

            $parameters = [
                 'api_key'       => $this->config->get('payment_vandar_api'),
                 'amount'        => $this->currency->format($order_info['total'], 'IRR', null, false),
                 'callback_url'  => ($this->url->link('extension/payment/vandar/callback',
                                                      'order_id=' . $order_info['order_id'])),
                 'factorNumber'  => $order_info['order_id'],
                 'mobile_number' => $telephone,
                 'description'   => $description,
            ];
            //var_dump( extension_loaded('curl'));
            //die();
            $result = $this->common($this->config->get('payment_vandar_send'), $parameters);
            $result = json_decode($result);
            //var_dump($result);
            //die();
            if (isset($result->status) && $result->status == 1) {

                $data['action'] = $this->config->get('payment_vandar_gateway') . $result->token;

            } else {

                $code = isset($result->errorCode) ? $result->errorCode : 'Undefined';
                $message
                      = isset($result->errorMessage) ? $result->errorMessage : $this->language->get('error_undefined');

                $data['error_warning']
                     = $this->language->get('error_request') . '<br/><br/>' . $this->language->get('error_code') . $code . '<br/>' . $this->language->get('error_message') . $message;
            }

        } else {

            $data['error_warning'] = $this->language->get('error_curl');
        }

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/extension/payment/vandar')) {

            return $this->load->view($this->config->get('config_template') . '/extension/payment/vandar', $data);

        } else {

            return $this->load->view('/extension/payment/vandar', $data);
        }
    }



    public function callback()
    {
        ob_start();

        $this->load->language('extension/payment/vandar');
        $this->load->model('checkout/order');
        $this->load->library('encryption');

        $this->document->setTitle($this->language->get('heading_title'));

        //$encryption = new Encryption($this->config->get('config_encryption'));

        $order_id = isset($this->session->data['order_id']) ? $this->session->data['order_id'] : false;
        $order_id = isset($order_id) ? $order_id : $this->request->get['order_id'];

        $order_info = $this->model_checkout_order->getOrder($order_id);

        //if ($this->currency->getCode() != 'RLS') {
        //
        //    $this->currency->set('RLS');
        //}

        $data['heading_title'] = $this->language->get('heading_title');

        $data['button_continue'] = $this->language->get('button_continue');
        $data['continue']        = $this->url->link('common/home', '', 'SSL');

        $data['error_warning'] = false;

        $data['continue'] = $this->url->link('checkout/cart', '', 'SSL');

        if ($this->request->get['token']) {

            $status = $this->request->get['token'];
            $token  = $this->request->get['token'];
            //$factor_number = $this->request->get['factorNumber'];
            //$message = $this->request->get['message'];

            if (isset($status)) {

                $parameters = [
                     'api_key' => $this->config->get('payment_vandar_api'),
                     'token'   => $token,
                ];

                $result = $this->common($this->config->get('payment_vandar_verify'), $parameters);
                $result = json_decode($result);

                //$factor_number = isset($result->factorNumber) ? $result->factorNumber : null;
                $trans_id = isset($result->transId) ? $result->transId : null;

                if ($order_id == $order_info['order_id']) {

                    if (isset($result->status) && $result->status == 1) {

                        //						$amount = @$this->currency->format($order_info['total'], $order_info['currency'], $order_info['value'], false);
                        $amount = $this->currency->format($order_info['total'], 'IRR', null, false) * 10;

                        if ($amount == $result->amount) {

                            $comment = $this->language->get('text_transaction') . $trans_id;

                            $this->model_checkout_order->addOrderHistory($order_info['order_id'],
                                                                         $this->config->get('payment_vandar_order_status_id'),
                                                                         $comment);

                        } else {

                            $data['error_warning'] = $this->language->get('error_amount');
                        }

                    } else {

                        //$code = isset($result->errorCode) ? $result->errorCode : 'Undefined';
                        $message
                             = isset($result->errorMessage) ? $result->errorMessage : $this->language->get('error_undefined');

                        $data['error_warning']
                             = $this->language->get('error_request') . '<br/><br/>' . $this->language->get('error_message') . $message;
                    }

                } else {

                    $data['error_warning'] = $this->language->get('error_invoice');
                }

            } else {

                $data['error_warning'] = $this->language->get('error_payment');
            }

        } else {

            $data['error_warning'] = $this->language->get('error_data');
        }

        if ($data['error_warning']) {

            $data['breadcrumbs'] = [];

            $data['breadcrumbs'][] = [
                 'text'      => $this->language->get('text_home'),
                 'href'      => $this->url->link('common/home', '', 'SSL'),
                 'separator' => false,
            ];

            $data['breadcrumbs'][] = [
                 'text'      => $this->language->get('text_basket'),
                 'href'      => $this->url->link('checkout/cart', '', 'SSL'),
                 'separator' => ' » ',
            ];

            $data['breadcrumbs'][] = [
                 'text'      => $this->language->get('text_checkout'),
                 'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
                 'separator' => ' » ',
            ];

            $data['header'] = $this->load->controller('common/header');
            $data['footer'] = $this->load->controller('common/footer');

            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/extension/payment/vandar_callback')) {

                $this->response->setOutput($this->load->view($this->config->get('config_template') . '/extension/payment/vandar_callback',
                                                             $data));

            } else {

                $this->response->setOutput($this->load->view('extension/payment/vandar_callback', $data));
            }

        } else {

            $this->response->redirect($this->url->link('checkout/success', '', 'SSL'));
        }
    }



    protected function common($url, $parameters)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}



?>
