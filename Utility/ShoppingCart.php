<?php

namespace  webultd\Payu\PaymentBundle\Utility;

use Symfony\Component\HttpFoundation\Session;

class ShoppingCart
{
    private $session;
    private $sessionId = 'shopping_cart';
    private $instance;

    private $initialData = array(
        'grand_total' => 0,
        'amount_net' => 0,
        'amount_gross' => 0,
        'tax' => 23, // TODO: tax from configuration
        'items' => array(),
    );


    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->session->start();

        $instance = $this->session->get($this->sessionId);

        if(!$instance) {
            $instance = $this->initialData;

            $this->session->set($this->sessionId, $instance);
        }

        $this->instance = $instance;
    }

    public function getItems()
    {
        return $this->instance['items'];
    }

    public function getGrandTotal()
    {
        return $this->instance['grand_total'];
    }

    public function getAmountNet()
    {
        return $this->instance['amount_net'];
    }

    public function getAmountGross()
    {
        return $this->instance['amount_gross'];
    }

    public function getTax()
    {
        return $this->instance['tax'];
    }

    public function addItem($productId, $name, $price, $quantity)
    {
        $priceNet = $price;
        $priceGross = $price * (1 + $this->getTax() / 100);

        $item = array(
            'product_id' => $productId,
            'Quantity' => $quantity,
            'Product' => array(
                'Name' => $name,
                'UnitPrice' => array(
                    'Gross' => $this->convertToLong($priceGross),
                    'Net' => $this->convertToLong($priceNet),
                    'Tax' => $this->getTax(),
                    'TaxRate' => $this->getTax(),
                    'CurrencyCode' => 'PLN', // TODO
                ),

            ),

        );

        $this->instance['items'][]['ShoppingCartItem'] = $item;
        //var_dump($this->instance); die;
        $this->recalculateCart();

        $this->session->set($this->sessionId, $this->instance);
    }

    public function deleteItem($productId)
    {
        foreach($this->instance['items'] as $key => $item) {
            if($item['product_id'] == $productId) {
                unset($this->instance['items'][$key]);
                $this->recalculateCart();

                $this->session->set($this->sessionId, $this->instance);
            }
        }
    }

    private function recalculateCart()
    {
        $grandTotal = 0;
        $amountNet = 0;
        $amountGross = 0;

        foreach($this->instance['items'] as $item) {
            $amountNet += $item['ShoppingCartItem']['Product']['UnitPrice']['Net'] * $item['ShoppingCartItem']['Quantity'];
        }

        $grandTotal = $amountGross = $amountNet * (1 + $this->getTax() / 100);

        $this->instance['grand_total'] = $grandTotal;
        $this->instance['amount_net'] = $amountNet;
        $this->instance['amount_gross'] = $amountGross;
    }

    public function clear()
    {
        $instance = $this->initialData;
        $this->session->set($this->sessionId, $instance);
        $this->instance = $instance;
    }

    private function convertToLong($number)
    {
        return number_format($number, 2, '', '');
    }
}