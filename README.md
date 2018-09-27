# LibDMF
Library for Data Model Framework.

Each Data Model (DM) represents one object persisted in a possibly remote, real-time-updated data source (DS) that supports SQL. It is designed to be:

##### Unique
Each DM is assigned with a universally-unique identifier called _DOID_  (does not necessarily comply with the UUID format) generated (non-cryptographically-secure) randomly.

##### Lingering
After a DM is asynchronously requested from the DS, it is cached locally for a period of time. This allows the caller to confidently reuse the objects without asynchronous logic.

##### Volatile
If a DM has not been used for a long time, it is removed from the cache. Therefore, under normal circumstances, the data requester should access the DM asynchronously, and should not store a reference to the DM.

##### Referencable
Alternatively, the data requester can apply a `DataModelUser` on a DM. If there is at least one `DataModelUser` active on a DataModel, it is guaranteed to retain in the cache.

##### Updating
Local changes to the DM are saved to the DS in _batches_. DS changes are also unpacked to the local object periodically. Implementations

##### Concurrent
Each DM contains a _"state"_ property, which is a unique identifier generated (non-cryptographically-secure) randomly. The _state_ is regenerated every time a _batch_ is saved to the DS, which allows tracking concurrent changes. LibDMF will modify the database only if it is still at the locally-known state. The batch is rejected if the local state is outdated by the time the batch arrives at the DS. This rejection can be handled by the context that created the batch.
