/* author: David Walker
   copyright: 2009 California State University
   version: $Id$
   package: Xerxes
   link: http://xerxes.calstate.edu
   license: http://www.gnu.org/licenses/
*/

USE xerxes;
CREATE LOGIN xerxes WITH PASSWORD = 'password';
CREATE user xerxes FOR LOGIN xerxes;
GRANT SELECT, INSERT, DELETE, UPDATE TO xerxes;