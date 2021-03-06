import { CVtimePoint } from './time.js'
import { Link } from './link.js'

export class CVproject {
	constructor(CVD,id,url,title,timeString1,timeString2,tags){
		this.self   = this
		this.CVD    = CVD
		this.id    = id    // WP post ID
		this.url   = url   // WP post href
		this.title = title // WP post title
		this._times = []   // times associated with the project
		// parse / handle times
		const t1 = new CVtimePoint( timeString1, this )
		const t2 = new CVtimePoint( timeString2, this )
		if( t1.etime < t2.etime ) {
			t1.position = 'start'
			t2.position = 'end'
			this._times = [ t1, t2 ]
		}else if( t1.etime == t2.etime || t1.etime > t2.etime){
			t2.position = 'only'
			this._times.push(t2)
		}else{ console.warn('Project has no times', this) }
		this.tags = tags // non-hierarchical tags
		// reserved for simulation
		this.x; this.y; this.vx; this.vy;
		// references to projects partially constituting this project
		this._children = []
		this._parents = []
		this.color
	}
	get start(){ return this._times[0] }
	get end(){ 
		return this._times.length > 0 ? this._times[this._times.length-1] : null
	}
	get duration(){ // approximate duration in seconds
		return Math.abs( this.end.etime - this.start.etime )
	}
	get nodes(){
		let n = this._times.concat( this._children.map(child=>child.start) )
		n.sort( (a,b)=>a.etime-b.etime )
		return n
	}
	get links(){ 
		// build internal links between the nodes of this project
		let l = []
		for( let i=1; i < this.nodes.length; i++ ){
			let source = this.nodes[i-1]
			let target = this.nodes[i]
			l.push( new Link( source, target, 'internal' ) )
		}
		return l
	}
	addChild(child){
		// append a reference to a given component project
		console.assert(child instanceof CVproject)
		if( ! this._children.includes(child) ){ this._children.push(child) }
		// bond the child to the parent as well
		child.addParent(this)
		// sort by start date
		this._children.sort( (a,b) => a.start.etime - b.start.etime )
	}
	addParent(parent){
		console.assert(parent instanceof CVproject)
		if( ! this._parents.includes(parent) ){ this._parents.push(parent) }
	}
	getNodeNear(timepoint){
		if( timepoint.etime >= this.end.etime ){ // effect comes after this project ends
			return this.end
		}else if(timepoint.etime <= this.start.etime){ // effect is at or before project
			return this.start
		}else{ // effect starts during project add a node to link from
			let newTimePoint = new CVtimePoint(timepoint.ts,this)
			newTimePoint.position = 'mid'
			this._times.push(newTimePoint)
			this._times.sort( (a,b)=>a.etime-b.etime )
			return newTimePoint
		}
	}
}
