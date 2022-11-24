# File Privacy / Inspect correctness of title "File Object ONLY" 
Disclaimer: This documentation does not warrant completeness or correctness. Please report any missing or wrong information using the [ILIAS issue tracker](https://mantis.ilias.de) or contribute a fix via [Pull Request](docs/development/contributing.md#pull-request-to-the-repositories).

##General Information
Please add a section outlining the relation of Repsoitory Object File to IRSS and the files that are not Object Type File. 

## Data being stored
- UserID of account that created the ILIAS file object is stored as "owner". 
- Creation Timestamp of the ILIAS file object is stored. 
- Update Timestamp  to the ILIAS file object is stored. 
- Ressource-Identifier for accessing the file through the Ressource Storage Service
- The File Object employs the following services, please consult the respective privacy.mds: Metadata, AccessControl, IRSS, News, Rating, Learning Progress, Object Service, ECS, Info-Screen  

## Data being presented
- To persons with "edit permission" (?!?) permission for the file object are presented with the Firstname, Lastname and Login of the owner on the Info-Tab. This is actually handled by Info-Service and not by File Object.   
- To persons with "edit settings" permission for the file object are presented with the following Firstname and Last-Name on the Versions-Tab. 

## Data being deleted
- Persons with "delete" permission for the file object can delete the file object. If no trash is activated then the data is deleted at once.  
- If the trash is activated the basic object, permission and learning progress and metadata data is deleted only, once the object is deleted from trash. User can empty the trash at Administration > System Settings an Maintenance > Repository Trash and Permissions.

## Data being exported
- Exports of the file object contain the owner data and userID for each version of the file object. 
