#!/bin/sh
set -eu

TEST=${1:-} # Test environment
UP=${2:-} # Whether to docker compose up and down the test stack
CACHE=${3:-} # Whether to override with docker-compose.build.yml

# Validation and normalization
if ! echo "$TEST" | grep -E '^(dev|prod|dns|snapshots)$' > /dev/null; then
    echo "Specify TEST as the first argument. E.g. 'dev', 'prod', 'dns'"
    exit 1
fi
if [ -n "$CACHE" ]; then
    CACHE='-f docker-compose.build.yml'
fi

SCRIPT_DIR=$( cd "$( dirname "$0" )" && pwd )
ERR=
setup_test() {
    cd "$SCRIPT_DIR"
    docker compose up -d
    if [ -n "$UP" ]; then
        setup
    fi
    run
}
cleanup_test() {
    ERR=$?
    if [ -n "$UP" ]; then
        cleanup
    fi
    docker compose stop
    if [ -z "$ERR" ] || [ "$ERR" = 0 ]; then
        echo "All tests succeeded"
    else
        echo "Some tests failed"
        echo "Exit code: $ERR"
        exit "$ERR"
    fi
}
trap cleanup_test INT TERM EXIT

echo "Testing..."
if [ "$TEST" = 'dev' ]; then
    setup() {
        (cd .. && docker compose -f docker-compose.yml $CACHE up --build -d)
    }
    run() {
        docker exec $( docker compose ps -q test-container-networking ) ./test-ready.sh
        docker exec $( docker compose ps -q test-container-networking ) ./test-routes.sh
        docker exec $( docker compose ps -q test-container-networking ) ./test-snapshots.sh
        docker exec $( docker compose ps -q test-container-networking ) ./test-internal-dns.sh
    }
    cleanup() {
        (cd .. && docker compose stop)
    }
fi
if [ "$TEST" = 'prod' ]; then
    setup() {
        (cd ../docs/full-bf2-stack-example && docker compose -f docker-compose.yml $CACHE up --build -d)
    }
    run() {
        docker exec $( docker compose ps -q test-container-networking ) ./test-ready.sh
        docker exec $( docker compose ps -q test-container-networking ) ./test-routes.sh
        docker exec $( docker compose ps -q test-host-networking ) ./test-endpoints.sh
        docker exec $( docker compose ps -q test-container-networking ) ./test-snapshots.sh
        docker exec $( docker compose ps -q test-container-networking ) ./test-internal-dns.sh
        docker exec $( docker compose ps -q test-container-networking ) ./test-external-dns.sh
    }
    cleanup() {
        (cd ../docs/full-bf2-stack-example && docker compose -f docker-compose.yml $CACHE stop)
    }
fi
if [ "$TEST" = 'dns' ]; then
    run() {
        docker exec $( docker compose ps -q test-container-networking ) ./test-ready.sh
        docker exec $( docker compose ps -q test-container-networking ) ./test-internal-dns.sh
        docker exec $( docker compose ps -q test-container-networking ) ./test-external-dns.sh
    }
fi
setup_test
