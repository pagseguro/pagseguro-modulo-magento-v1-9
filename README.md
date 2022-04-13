
![N|Solid](https://upload.wikimedia.org/wikipedia/commons/8/80/Logo_PagSeguro.png)

# PagSeguro - Módulo Magento 1.9
## _API Charge_


## Descrição
Esse módulo é compatível com a versão 1.9 do Magento. Ele utiliza a API Charge do PagSeguro, sendo capaz de fazer transações para pagamentos com boleto, um cartão de crédito ou dois cartões de crédito.

## Instalação utilizando Magento Connect

- Baixe o arquivo PagSeguro_Payment.1.0.0.tgz
- Certifique-se de não ter uma versão antiga do módulo instalada em sua loja
- No painel da sua loja Magento acesse o menu Sistema -> Magento Connect -> Magento Connect Manager
- Efetue o login usando as mesmas credencias utilizadas para acessar o painel de sua loja
- Em "Direct package file upload" selecione o arquivo que você baixou no primeiro passo e clique e clique em Upload
- Você poderá acompanhar o processo de instalação do módulo ao final da página

## Instalação manual

- Faça o download dos arquivos deste repositório
- Cole os arquivos na raiz da sua loja
- Certifique-se que o arquivo app/etc/modules/PagSeguro_Payment.xml esteja com a propriedade <active> com o valor true
- Limpe o cache da sua loja
- O módulo estará disponível na sua loja

## Funcionalidades

#### PIX
- Fatura
- Estorno
- Atualização do status do pedido utilizando Cron
- Atualização de status do pedido utilziando callback

#### Boleto
- Fatura
- Estorno
- Atualização do status do pedido utilizando Cron
- Atualização de status do pedido utilziando callback

#### Cartão de Crédito
- Autorização
- Fatura
- Estorno
- Pagamento com checkout transparente
- Pagamento com cartão salvo
- Atualização do status do pedido utilizando Cron
- Atualização de status do pedido utilziando callback
  \* Para pagamentos com dois cartões, caso um dos cartões não seja aprovado, o pedido será cancelado automaticamente e todos os estornos necessários serão realizados.