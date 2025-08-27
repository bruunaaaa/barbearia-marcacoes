CREATE DATABASE crud_1;
USE crud_1;
alter table clientes change name nome varchar(100);
create table clientes 
(id INT AUTO_INCREMENT PRIMARY KEY,
nome VARCHAR(100) not null,
telemovel varchar(20) not null
);
create table servicos
(id int auto_increment primary key,
nome_servico varchar(255) not null,
preco decimal(10,2) not null,
duracao int not null,
descricao varchar(255)
);
create table marcacoes
(id INT AUTO_INCREMENT PRIMARY KEY,
cliente_id int not null,
servico_id int not null,
data date not null,
hora time not null,
status enum('marcada','concluida','cancelada') not null default 'marcada',
FOREIGN KEY (cliente_id) REFERENCES clientes(id),
FOREIGN KEY (servico_id) REFERENCES servicos(id)
);

INSERT INTO servicos (nome_servico, preco, duracao, descricao) VALUES
('Corte', 15.00, 40, 'Corte masculino tradicional'),
('Barba', 10.00, 20, 'Aparar e modelar barba'),
('Corte + Barba', 22.00, 50, 'Corte de cabelo e barba completa'),
('Corte infantil', 12.00, 25, 'Corte para crianças até 12 anos'),
('Sobrancelha', 8.00, 10, 'Aparar e modelar sobrancelhas'),
('Luzes ou mechas', 35.00, 60, 'Coloração parcial para dar destaque'),
('Pintura', 20.00, 50, 'Pintar o cabelo');

select * from marcacoes;