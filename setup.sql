CREATE TABLE IF NOT EXISTS policy_ideas (
	id integer PRIMARY KEY autoincrement,
   	title text,
	description text,
	icon text
) ;

CREATE TABLE IF NOT EXISTS votes (
	session_id text,
   	policy_idea_id integer,
	vote integer
) ;

INSERT INTO policy_ideas (title, description, icon)
VALUES('Prepoved vstopa vozil na dizel', "Nunc vitae vulputate arcu, vel ultrices elit. Duis ac malesuada nisi. 
Proin quam sem, convallis ac sapien eu, elementum ultrices dui. Nam vel lacinia lectus. 
Praesent ullamcorper sed tortor sit amet molestie. Maecenas ut sollicitudin metus, sed auctor leo. Donec sit amet malesuada elit.", "nocar");

INSERT INTO policy_ideas (title, description, icon)
VALUES('Izboljšanje javnega prevoza', "Nunc vitae vulputate arcu, vel ultrices elit. Duis ac malesuada nisi. 
Proin quam sem, convallis ac sapien eu, elementum ultrices dui. Nam vel lacinia lectus. 
Praesent ullamcorper sed tortor sit amet molestie. Maecenas ut sollicitudin metus, sed auctor leo. Donec sit amet malesuada elit.", "bus");

INSERT INTO policy_ideas (title, description, icon)
VALUES('Zaprtje širšega centra za vozila nestanovalcev', "Nunc vitae vulputate arcu, vel ultrices elit. Duis ac malesuada nisi. 
Proin quam sem, convallis ac sapien eu, elementum ultrices dui. Nam vel lacinia lectus. 
Praesent ullamcorper sed tortor sit amet molestie. Maecenas ut sollicitudin metus, sed auctor leo. Donec sit amet malesuada elit.", "nocar");

