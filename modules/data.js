// container class for all necessary data
class chartaData {
	constructor(json_data){
		// lets keep all structure from JSON to this function
		this._events = [];
		this._logicalLinks = [];
		this._structuralLinks = [];
		// convert events to event objects
		for( let e of json_data['events'] ){
			this._events.push( new CVevent( 
				e.id, e.url, e.title,
				e.start, e.end, e.strata, e.tags // start & end may be undefined
			) );
		}
		// convert logical links to link objects
		for( let l of json_data['links'] ){
			this._logicalLinks.push( new Link(
				this.eventByID(l.source),
				this.eventByID(l.target),
				l.type
			) );
		}
	}
	// accessors 
	get events(){ return this._events; }
	get links(){ 
		//return this._logicalLinks;
		let links = [];
		for(let e of this._events){
			if(e.links[0]){ links.push(e.links[0]) };
		}
		return links;
	}
	eventByID(event_id){
		for(let event of this._events){
			if( event_id == event.id ){ return event; }
		}
		return event_id;
	}
	get nodes(){
		let nodes = [];
		for( let e of this._events ){
			nodes.push( e.start );
			if( e.duration > 0 ){
				nodes.push( e.end );
			}
		}
		return nodes;
	}
}
