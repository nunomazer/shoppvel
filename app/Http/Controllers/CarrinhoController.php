<?php

namespace Shoppvel\Http\Controllers;

use Illuminate\Http\Request;
use Shoppvel\Http\Requests;
use Shoppvel\Models\Carrinho;
use Shoppvel\Models\Produto;

class CarrinhoController extends Controller {

    private $carrinho = null;

    function __construct() {
        parent::__construct();
        $this->carrinho = new Carrinho();
    }

    function anyAdicionar(Request $request, $id) {
        if ($id == null) {
            return \Redirect::back()
                            ->withErrors('Nenhum código de produto informado para adicionar ao carrinho.');
        }
        // se um id foi passado e a adição ao carrinho está ok
        if ($this->carrinho->add($id, $request->get('qtde'))) {
            return redirect()->route('carrinho.listar')
                            ->with('mensagens-sucesso', 'Produto adicionado ao carrinho');
        }

        return \Redirect::back()->withErrors('Erro ao adicionar produto no carrinho');
    }

    function getListar() {
        $models['itens'] = $this->carrinho->getItens();
        $models['total'] = $this->carrinho->getTotal();
        if ($models['itens']->count() > 0) {
            $models['pagseguro'] = $this->checkout();
        }
        return view('frente.carrinho-listar', $models);
    }

    /**
     * Rotinas a serem implementadas na integração completa com Pagseguro
     */
    public function getCheckout() {
        echo('REDIRECT <br/>');
        dd(func_get_args());
    }

    public function getCheckoutNotification() {
        echo('NOTIFICATON <br/>');
        dd(func_get_args());
    }

    function getEsvaziar() {
        $this->carrinho->esvaziar();
        return redirect('/')->with('mensagens-sucesso', 'Carrinho vazio');
    }

    /**
     * Função provisória de checkout, jogando todo o contole no ambiente
     * de administração do Pagseguro
     * Neste momento somente é chamado pelo listar carrinho para montar
     * o link para o Pagseguro
     * 
     * @return type
     */
    function checkout() {
        $itens = [];
        foreach ($this->carrinho->getItens() as $item) {
            $itens[] = [
                'id' => $item->produto->id,
                'description' => $item->produto->nome,
                'quantity' => $item->qtde,
                'amount' => $item->produto->preco_venda,
            ];
        }
        $dadosCompra = [
            'items' => $itens,
        ];

        $checkout = \PagSeguro::checkout()->createFromArray($dadosCompra);
        $models['info'] = $checkout->send(\PagSeguro::credentials()->get());
        return $models;
    }

}
