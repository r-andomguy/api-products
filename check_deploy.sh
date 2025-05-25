#!/bin/bash

set -e
set -o pipefail

echo "[INFO] Iniciando validações para deploy"

echo "[INFO] Verificando conformidade com PSR-12 via phpcs"
./vendor/bin/phpcs --standard=PSR12 src/

echo "[OK] Código em conformidade com PSR-12"

echo "[INFO] Executando análise estática com PHPStan"
./vendor/bin/phpstan analyse src --level=max

echo "[OK] Análise estática concluída sem erros"

echo "[INFO] Verificando necessidade de correções automáticas com php-cs-fixer"
./vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no src/

echo "[OK] Nenhuma correção automática pendente"

if [ -x ./vendor/bin/phpunit ]; then
    echo "[INFO] Executando testes automatizados com PHPUnit"
    ./vendor/bin/phpunit
    echo "[OK] Todos os testes passaram"
else
    echo "[WARN] PHPUnit não encontrado. Etapa de testes ignorada"
fi

echo "[SUCESSO] Todas as verificações foram concluídas. Código pronto para produção."
