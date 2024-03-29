# DESAFIO BACKEND

## Configuração do Ambiente

### Requisitos

- _PHP >= 8.0_ e [extensões](https://www.php.net/manual/pt_BR/extensions.php) (**não esquecer de instalar as seguintes extensões: _pdo_, _pdo_sqlite_ e _sqlite3_**);
- _SQLite_;
- _Composer_.
- Digitar no terminal: php -S localhost:8000

### Instalação

- Instalar dependências pelo composer com `composer install` na raiz do projeto;
- Servir a pasta _public_ do projeto através de algum servidor.
  (_Sugestão [PHP Built in Server](https://www.php.net/manual/en/features.commandline.webserver.)_)

## Sobre o Projeto

- O cliente XPTO Ltda. contratou seu serviço para realizar alguns ajustes em seu sistema de cadastro de produtos;
- O sistema permite o cadastro, edição e remoção de _produtos_ e _categorias de produtos_ para uma _empresa_;
- Para que sejam possíveis os cadastros, alterações e remoções é necessário um usuário administrador;
- O sistema possui categorias padrão que pertencem a todas as empresas, bem como categorias personalizadas dedicadas a uma dada empresa. As categorias padrão são: (`clothing`, `phone`, `computer` e `house`) e **devem** aparecer para todas as _empresas_;
- O sistema tem um relatório de dados dedicado ao cliente.

## Sobre a API

As rotas estão divididas em:

- _CRUD_ de _categorias_;
- _CRUD_ de _produtos_;
- Rota de busca de um _relatório_ que retorna um _html_.

**Atenção**, é bem importante que se adicione o _header_ `admin_user_id` com o id do usuário desejado ao acessar as rotas para simular o uso de um usuário no sistema.

A documentação da API se encontra na pasta `docs/api-docs.pdf`

- A documentação assume que a url base é `localhost:8000` mas você pode usar qualquer outra url ao configurar o servidor;
- O _header_ `admin_user_id` na documentação está indicado com valor `1` mas pode ser usado o id de qualquer outro usuário caso deseje (_pesquisando no banco de dados é possível ver os outros id's de usuários_).

Caso opte por usar o [Insomnia](https://insomnia.rest/) o arquivo para importação se encontra em `docs/insomnia-api.json`.
Caso opte por usar o [Postman](https://www.postman.com/) o arquivo para importação se encontra em `docs/postman-api.json`.

## Sobre o Banco de Dados

- O banco de dados é um _sqlite_ simples e já vem com dados preenchidos por padrão no projeto;
- O banco tem um arquivo de backup em `db/db-backup.sqlite` com o estado inicial do projeto caso precise ser "resetado".

## Demandas

Abaixo, as solicitações do cliente:

### Categorias

- [ ok] A categoria está vindo errada na listagem de produtos para alguns casos
      (_exemplo: produto `blue trouser` está vindo na categoria `phone` e deveria ser `clothing`_);
- [ ok ] Alguns produtos estão vindo com a categoria `null` ao serem pesquisados individualmente (_exemplo: produto `iphone 8`_);
- [ ok ] Cadastrei o produto `king size bed` em mais de uma categoria, mas ele aparece **apenas** na categoria `furniture` na busca individual do produto.

### Filtros e Ordenamento

Para a listagem de produtos:

- [ ok] Gostaria de poder filtrar os produtos ativos e inativos;
- [ ok ] Gostaria de poder filtrar os produtos por categoria;
- [ ok ] Gostaria de poder ordenar os produtos por data de cadastro.

### Relatório

- [ ok] O relatório não está mostrando a coluna de logs corretamente, se possível, gostaria de trazer no seguinte formato:
      (Nome do usuário, Tipo de alteração e Data),
      (Nome do usuário, Tipo de alteração e Data),
      (Nome do usuário, Tipo de alteração e Data)
      Exemplo:
      (John Doe, Criação, 01/12/2023 12:50:30),
      (Jane Doe, Atualização, 11/12/2023 13:51:40),
      (Joe Doe, Remoção, 21/12/2023 14:52:50)

### Logs

- [ Ok ] Gostaria de saber qual usuário mudou o preço do produto `iphone 8` por último.

### Extra

- [ ] Aqui fica um desafio extra **opcional**: _criar um ambiente com_ Docker _para a api_.

**Seu trabalho é atender às 7 demandas solicitadas pelo cliente.**

Caso julgue necessário, podem ser adicionadas ou modificadas as rotas da api. Caso altere, por favor, explique o porquê e indique as alterações nesse `README`.

Sinta-se a vontade para refatorar o que achar pertinente, considerando questões como arquitetura, padrões de código, padrões restful, _segurança_ e quaisquer outras boas práticas. Levaremos em conta essas mudanças.

Boa sorte! :)

## Suas Respostas, Duvidas e Observações

## Com relação aos problemas: 

- Sobre os produtos vierem errados:

verifiquei que as consultas sql estavam ok, o problema era a lógica no endpoint getOne. A baixo listo o que foi feito:

//getOne estava acessando como um objeto os dados do database sqlite, acessei a propriedade direto como array, por isso null em alguns casos;

//FIX: antes de tentar acessar as propriedades do objeto $product, verificamos se ele é realmente um objeto;

//FIX: Service já entregar o objeto quase todo pronto para o controller;

//FEAT: Remoção de category em casos de produtos com + de uma categoria, mantive o campo category nos que não tem, para que não quebre no frontend;

//FEAT: Acessar "categories" no json, caso consumir, esse é o nome da propriedade do endpoint;

//FEAT: Criei um array para as categories, frontend não deve acessar apenas a propriedade, mas também consumir o array


- Sobre os logs

Verifiquei que o endpoint usava o service getAll, mudei para o getLog

//FEAT: Fiz a relação de id de produtos, com o nome dos produtos;

//FIX: Modifiquei a data do banco de dados para o padrão dd/mm/yyyy hh:mm:ss;

//FEAT: Adicionei try e catchs para ajudar  na depuração em casos de erros;

//FEAT: Modifiquei o controllador para retornar conforme pedido os logs mais humanizados;

//FEAT: Adicionei ainda o nome do item que foi manipulado, conforme o registro


- Sobre novos filtros para a api

Criei um endpoint para poder fazer a pesquisa por categoria e se o produto está ativo ou não

//FEAT: Criado nova rota para buscar por categoria;

//FEAT: Criado nova rota para buscar por status;

//FEAT: Criado novo service dedicado a buscar  por esses dois critérios;

//FEAT: Criado uma rota exlusiva para isso;

- Segue os endpoints da nova rota:

- http://localhost:8000/products/filter?active=1  => Busca APENAS os produtos ativos ou inativos;
- http://localhost:8000/products/filter?category=food => Busca APENAS  os produtos relacionados à categoria (food, furniture, clothing, house...);
- http://localhost:8000/products/filter?category=food => Busca APENAS  os produtos relacionados à categoria (food, furniture, clothing, house...);
- http://localhost:8000/products/filter?category=house&active=0 => Busca  todos os produtos pelo status (active/inactive) que estão relacionados à categoria (a ordem não influência).
- http://localhost:8000/products/filter?category=house&active=1&&sort=asc  =>  Filtra pela categoria e ordena crescente
- admin_user_id =1  => Deve ser passado nas consultas
