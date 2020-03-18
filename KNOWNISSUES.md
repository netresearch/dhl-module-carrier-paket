# Known Issues

All notable known issues and bugs with this project will be documented in this file.

## Configuring allowed countries on Website/Store scope (DHLGW-808)

Under certain circumstances, the "Allow for Specific Countries" configuration option
can become unresponsive on scopes other than default/global.
This includes not beeing able to update the "Use Default" setting.

To resolve this issue, remove all entries for
`carriers/dhlpaket/sallowspecific` and
`carriers/dhlpaket/specificcountry` in the affected scope
from the `core_config_data` table and clear the cache.
