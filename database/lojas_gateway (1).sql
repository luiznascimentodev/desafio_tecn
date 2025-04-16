CREATE TABLE IF NOT EXISTS "lojas_gateway" (
    "id" serial primary key,
    "id_loja" INT,
    "id_gateway" INT
);
INSERT INTO "lojas_gateway" VALUES
    (1,90,1),
    (2,92,2),
    (3,115,1),
    (4,98,1),
    (5,97,1),
    (6,94,1);
