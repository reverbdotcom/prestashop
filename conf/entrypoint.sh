#!/bin/sh

#!/bin/sh -e

/tmp/docker_run.sh

echo "\n* Almost ! Starting Apache now\n";
exec apache2 -DFOREGROUND
