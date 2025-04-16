CREATE TABLE IF NOT EXISTS "gateways" (
    "id" serial primary key,
    "descricao" TEXT,
    "endpoint" TEXT
);
INSERT INTO "gateways" VALUES
    (1,'PAGCOMPLETO','https://api11.ecompleto.com.br/'),
    (2,'CIELO','https://api.cielo.com.br/v1/transactions/'),
    (3,'PAGSEGURO','https://api.pagseguro.com.br/transactions/');
