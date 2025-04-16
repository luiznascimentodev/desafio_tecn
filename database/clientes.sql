CREATE TABLE IF NOT EXISTS "clientes" (
    "id" serial primary key,
    "nome" TEXT,
    "cpf_cnpj" VARCHAR(50),
    "email" TEXT,
    "tipo_pessoa" TEXT,
    "data_nasc" DATE,
    "id_loja" INT
);
INSERT INTO "clientes" VALUES
    (8796,'Emanuelly Alice Alessandra de Paula','96446953722','emanuellyalice@ecompleto.com.br','F','1988-01-18',90),
    (5789,'Renato Ryan Lopes','78891957615','renato_ryan@ecompleto.com.br','F','1947-02-08',92),
    (6748,'Kauê Bryan Souza','55782338806','kauesouza@ecompleto.com.br','F','1945-06-27',90),
    (6872,'Samuel Emanuel Castro','85673855800','samuel.castro@ecompleto.com.br','F','1988-11-05',115),
    (6716,'Raquel Nicole Moura','36118844720','raquelnicole_moura@ecompleto.com.br','F','1990-02-20',98),
    (4802,'Fernando Julio Ramos','20499776461','fernando_julio99@ecompleto.com.br','F','1999-09-11',97),
    (9484,'Kevin Yuri Pedro Lopes','95829123088','kevinpedro@ecompleto.com.br','F','1996-06-03',94),
    (1830,'Thales André Pereira','13440817709','samuel.castro@ecompleto.com.br','F','1995-04-07',90),
    (2280,'Heloisa Valentina Fabiana Moura','99386767660','heloisavalentina@ecompleto.com.br','F','1984-12-12',92);
