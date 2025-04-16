CREATE TABLE IF NOT EXISTS "pedidos_pagamentos" (
    "id" SERIAL PRIMARY KEY,
    "id_pedido" INT,
    "id_formapagto" INT,
    "qtd_parcelas" INT,
    "retorno_intermediador" TEXT,
    "data_processamento" TEXT,
    "num_cartao" VARCHAR(50),
    "nome_portador" VARCHAR(50),
    "codigo_verificacao" int,
    "vencimento" varchar(10)
);
INSERT INTO "pedidos_pagamentos" VALUES
    (103013,98302,3,4,null,null,'5236387041984690','Elisa Adriana Barbosa','319','2022-08'),
    (103014,98303,3,2,null,null,'5372472213342610','Renato Ryan','848','2022-03'),
    (103015,98304,1,1,null,null,null,null,null,null),
    (103016,98305,2,1,null,null,null,null,null,null),
    (103017,98306,3,1,null,null,'4929521310619600','Raquel Moura','721','2023-03'),
    (103018,98307,3,1,null,null,'4275824466404380','Fernando Julio','482','2022-05'),
    (103019,98308,3,5,null,null,'5167913943407160','Kevin Pedro','441','2022-10'),
    (103020,98309,2,1,null,null,null,null,null,null),
    (103021,98310,1,1,null,null,null,null,null,null);
