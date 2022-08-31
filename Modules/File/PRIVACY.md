# File Privacy
Disclaimer: This documentation does not warrant completeness or correctness. Please report any missing or wrong information using the [ILIAS issue tracker](https://mantis.ilias.de) or contribute a fix via [Pull Request](docs/development/contributing.md#pull-request-to-the-repositories).


## Data being stored

### Entry in the general object database table with the following information:
- Identifier of the user account who created the ILIAS file object
- Identifier of the ILIAS file object
- Title of the ILIAS file object
- Description of the ILIAS file object
- Timestamp of the creation of the ILIAS file object
- Timestamp of the last change to the ILIAS file object

### Entry in a file specific database table with the following information:
- Identifier of the ILIAS file object (makes the following information linkable to the entry above and therefore the user who created it)
- Name of the uploaded file 
- Type of the uploaded file
- Size of the uploaded file
- Current version
- Max version
- Rating
- Page-count (for pdfs)
- Ressource-Identifier for accessing the file through the Ressource Storage Service (from ILIAS 7 onwards)

### Entry in the write event database table with the following information:
- Identifier of the user account who created or made changes to the ILIAS file object
- Identifier of the ILIAS file object
- Timestamp of the creation or change
- Action (whether it was a creation or change)

### Entry in the read event database table with the following information:
- Identifier of the user account who accessed the ILIAS file object
- Identifier of the ILIAS file object
- Timestamp of the most recent access by the user
- Timestamp of the first access by the user
- Number of accesses by the user
- Number of seconds spent accessing the object by the user

### File stored in the file system:
- Directly stored at a logically derivable location with its original name before ILIAS 7
- Stored with an anonimized name at a non-derivable location by the Ressource Storage Service from ILIAS 7 onward
  

## Data being presented

### To people with "visible" permission for the file object:
- Info Tab with the following information:
  - File-Name
  - File-Type
  - Ressource-Identifier
  - Storage-Identifier
  - File-Size
  - Version
  - Date and Time at which the version was created
  - Login-Name of the user who uploaded the version 

### To people with "read" permission for the file object:
- Info-Tab with the same information as with "visible" permission
- Possibility to download and therefore see the contents of a file

### To people with "edit settings" permission for the file object:
- Versions-Tab with an overview of all versions of a file in a table with the following information for each version:
  - Version-Number
  - Date and Time at which the version was created
  - First- and Last-Name of the user who uploaded the version
  - File-Name
  - Title of the ILIAS file object
  - File-Size
  - Version-Type (Initial, New)

### To the user who created the file:
- Possibility to download and therefore see the contents of a file
- Versions-Tab with the same information as with "edit settings" permission
- Info-Tab with the same information as with "visible" permission as well as the following additional information:
  - Date and Time at which the file object was was created
  - Login-Name of the user who created the file object


## Data being deleted
- People with "delete" permission for the file object can delete the file object and all its associated personal information to the trash.


## Data being exported
- Exports of the file object contain all data listed in the stored data section except for the read_event and write_event entries.