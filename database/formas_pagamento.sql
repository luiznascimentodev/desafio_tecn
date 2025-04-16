CREATE TABLE IF NOT EXISTS formas_pagamento (
    "id" serial primary key,
    "descricao" VARCHAR(50)
);
INSERT INTO formas_pagamento VALUES
    (1,'Boleto Bancário'),
    (2,'Depósito Bancário'),
    (3,'Cartão de Crédito');
