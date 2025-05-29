# Sistema de Gestão de Frota

Sistema completo para gerenciamento de frota de veículos, incluindo controle de manutenções, motoristas e relatórios.

## Funcionalidades

- Gestão de veículos
- Controle de manutenções
- Gestão de motoristas
- Lançamento de Multas e Infrações
    - Objetivo: Controlar multas recebidas por veículo e responsabilizar condutores.
    - Campos principais: Veículo, Motorista responsável, Tipo de infração, Data, Valor, Situação (paga/não paga), Documento anexo, Descrição.
    - Extras: Relatório por motorista ou veículo, Geração de aviso automático.
- Dashboard com indicadores
- Histórico de manutenções
- Controle de quilometragem
- Agendamento de revisões

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Composer
- Extensões PHP:
  - PDO
  - PDO_MySQL
  - GD
  - ZIP
  - XML

## Instalação

1. Abrir o banco de dados no navegador:

- http://localhost/phpmyadmin/


2. site para acesso total ao painel:

- http://localhost/frota/login.php


3. Configure o banco de dados:
- Crie um banco de dados MySQL
- Importe o arquivo `database/schema.sql`
- Importe o arquivo `database/update_schema.sql`
- Configure as credenciais em `config/database.php`

4. Configure o servidor web:
- Aponte o DocumentRoot para o diretório do projeto
- Certifique-se que o mod_rewrite está habilitado (Apache)
- Configure as permissões dos diretórios:
```bash
chmod -R 755 .
chmod -R 777 uploads/
```

## Estrutura do Projeto

```
frota/
├── config/             # Configurações
├── database/          # Scripts SQL
├── includes/          # Arquivos de template
├── models/            # Modelos
├── uploads/           # Arquivos enviados
├── vendor/            # Dependências
├── composer.json      # Configuração do Composer
└── README.md          # Este arquivo
```

## Uso

1. Acesse o sistema pelo navegador
2. Faça login com suas credenciais
3. Utilize o menu para navegar entre as funcionalidades
4. Consulte o histórico de manutenções
5. Gere relatórios quando necessário


## Relatórios

O sistema gera relatórios em dois formatos:

1. PDF:
   - Relatório detalhado
   - Gráficos e estatísticas
   - Formatação profissional

2. Excel:
   - Dados brutos
   - Filtros e ordenação
   - Cálculos e fórmulas

## Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-feature`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova feature'`)
4. Push para a branch (`git push origin feature/nova-feature`)
5. Crie um Pull Request

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes. 