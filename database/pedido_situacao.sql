CREATE TABLE IF NOT EXISTS pedido_situacao (
    "id" serial primary key,
    "descricao" VARCHAR(50)
);
INSERT INTO pedido_situacao VALUES
    (1,'Aguardando Pagamento'),
    (2,'Pagamento Identificado'),
    (3,'Pedido Cancelado');
