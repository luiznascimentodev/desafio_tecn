# Desafio Técnico – Integração de Pagamentos (MVC)

Este projeto é uma solução enxuta para processamento de pagamentos via gateway externo, estruturado em camadas seguindo o padrão MVC (Model-View-Controller) em PHP.

## Objetivo

Processar automaticamente pedidos pendentes de pagamento (cartão de crédito) para lojas que utilizam o gateway PAGCOMPLETO, integrando com a API externa, atualizando o status dos pedidos e registrando o retorno da transação.

## Estrutura do Projeto

```
├── config/           # Configurações do sistema (ex: banco de dados)
├── controllers/      # Controllers (pontos de entrada das requisições)
├── database/         # Scripts SQL para estrutura e dados de exemplo
├── gateways/         # Integração com gateways de pagamento
├── models/           # Modelos e acesso a dados
├── services/         # Lógica de negócio (serviços)
├── utils/            # Utilitários (ex: logger)
├── views/            # Views (respostas JSON)
├── index.php         # Ponto de entrada simples/teste do servidor
├── README.md         # Este arquivo
```

## Principais Arquivos e Pastas

- **config/database.php**: Configuração do banco de dados PostgreSQL.
- **controllers/process_payments.php**: Endpoint para processar pagamentos pendentes (POST).
- **services/PaymentService.php**: Lógica de negócio para processar pedidos e pagamentos.
- **gateways/PagcompletoGateway.php**: Integração com a API do gateway PAGCOMPLETO.
- **models/Database.php**: Classe de acesso ao banco de dados.
- **utils/Logger.php**: Função utilitária para logs padronizados.
- **views/json_response.php**: View para resposta JSON padronizada.
- **database/**: Scripts SQL para criar e popular as tabelas de teste.

## Como Executar

1. **Configure o banco de dados**

   - Edite `config/database.php` com os dados do seu PostgreSQL.
   - Importe os arquivos `.sql` da pasta `database/` para criar e popular as tabelas.

2. **(Opcional) Configure variáveis sensíveis**

   - Se desejar, use um arquivo `.env` para tokens e endpoints.

3. **Inicie o servidor PHP**

   ```bash
   php -S localhost:8000
   ```

4. **Teste o endpoint no Insomnia/Postman**
   - Método: `POST`
   - URL: `http://localhost:8000/controllers/process_payments.php`
   - Não é necessário enviar body.
   - A resposta será um JSON com o resultado do processamento.

## Fluxo Resumido

1. O controller recebe a requisição POST.
2. O serviço (`PaymentService`) busca pedidos pendentes no banco.
3. Para cada pedido, valida os dados, integra com o gateway e atualiza o status conforme o retorno.
4. O resultado é retornado em formato JSON pela view.

## Boas Práticas Adotadas

- **MVC**: Separação clara entre controller, service, model, gateway, view e utilitários.
- **Validação de dados**: Antes de processar pagamentos, todos os campos obrigatórios são validados.
- **Tratamento de erros**: Exceções e falhas externas não quebram o sistema.
- **Logs padronizados**: Utilitário centralizado para logs.
- **Configuração externa**: Possibilidade de uso de `.env` para dados sensíveis.
- **Código limpo e comentado**: Foco em clareza e manutenção.

## Observações

- O projeto evita dependências externas e prioriza simplicidade.
- Para produção, remova a exibição de erros e proteja endpoints sensíveis.
- Consulte os comentários nas funções principais para entender detalhes do fluxo.

---

Dúvidas ou sugestões? Fique à vontade para abrir uma issue ou contribuir!
