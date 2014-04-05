banglang
========

language parser and executor dev test in PHP

This project was made in PHP to help me define the language, I intend to rewrite it in C++ once I have a clear understanding of how it should work
The code's not perfect, I only spent a few days in total writing it


developed on windows
untested for linux


-- building with phalanger --

change $compPath in build.php to the path of your phalangers phpc.exe

$PHP build.php



-- use --

$PHP bang.php script.bang

or if compiled

bang.exe script.bang


-- current capabilities --

current functions:
	echo ({variable to write}) : write to the console
	getFile ({string file path}) : get the string contents of a file or website
	saveFile ({string file path}, {string variable to write}) : save a string to a file
	isset ({variable to check}) : check if a variable is set
	dump ({variable to dump}) : dumps the variable type and contents to the console
	exec ({string the command to execute}) : executes on the command line
	lineIn ({string prompt}) : waits for user input from the command line
	makeThread ({string name of the function to thread}, [{function argument},]) : initiates a thread and returns its id
	startThread ({int thread id}) : start the thread
	joinThread ({int thread id}) : waits for the thread to finish

using pthreads causes a seg fault
clr threads working when compiled with phalanger (issue with scope)

including files:
	#include 'file path'

including packages:
	#package 'package name'



-- options --

-i parse / execution info
-p force parse (dont use parsed script from cache)
-x only execute (executed an already parsed script)

using options examples:

bang.exe script.lang -i
bang.exe script.lang -ix
$PHP src/main.php script.lang -ip


-- TODO --

brackets/bidmas

classes
threads

scope operators
error handeling
pointers



-- packages --

file - manipulation
web - util
string - manipulation
array - manipulation
types - conversion



-- how classes should work --

c = new cname("hi")
c.fn(args)


class cname : cbase {

	public name1
	private name2

	init v1 {  // default public
		this.parent.init(v1)
	}
	
	func fn args {
		this.name2 = args
	}
	fn2 {
		return this.mess + this.name2
	}
	private prvfn {

	}
}


class cbase {
	protected mess

	init v1 {
		this.mess = v1
	}
}



-- class info

name 
extends - class name or false

public vars
private vars
protected vars

public functions
private functions
protected functions








notes
--------------------------------------------------------------------------------------

statement = validseq then isnext(operator) || validseq then isnext(openfunc) || validseq then isnext(statementEnd)
function = validseq then optional validseqs split by white space then openstructure



function a b {
	echo("hi");
}


if "hello" * 3 == b {
	echo("hi")
}


hello(a b c d e)
hi()
$ int a = hello (j);

	a;
	hi = some()
	hello()
	hello(a)
	hello(a,b)
	hello(a b c)
	hello(hi() howare) + ho + 9 + "jjkjb"
	hello('his', boo(hi))
	$hi(havethis()+'hi')
	@hello(9 1-(3*hello))
	hello(a()*7 b)
	hello(@hi()*7 hello)
	$hi = something()
	hi = hello(a);


----------------------------------------------------------------------------------------

$files = [
	'home/file1' : 'balahblah'
	'home/file2' : 'blah2lshavdfg'
	'home/file3' : 'dssvyus'
]

if saveFiles(files) {
	echo "All files saved" 
}
else {
	echo "Error saving files"
}

$&saveFiles saveContents {
	$saved = []
	$out = false;

	$saveDone dir ok {
		saved[dir] = ok
		$foundAll = true
		for saveContents as dir : c {
			if !saved[dir] {
				foundAll = false
				break
			}	
		}
		if foundAll {
			out = true
		}
	}

	$thread saveFile dir content {
		ok = file.save(dir content)
	}
	end {
		saveDone(dir, ok)
	}

	for saveContents as dir : content {
		saveFile(dir content)
	}
}
end {
	return out
}

----------------------------------------------------------------------------------------



$class session {
	public id
	private vars = []
	
	init sessid {
		id = sessid
		for sessid as char {
			if (!alpha(char) && !numeric(char)) {
				return false
			}
		}
		return true
	}
	
	set name val {
		vars[name] = val
	}
	get name {
		return vars[name]
	}
	getVars {
		return vars
	}
}

sessions = []

&thread SERVER requestData {
	sess;
	if (sessions[requestData.cookies._]) {
		sess = sessions[requestData.cookies._]
	}
	else {
		sess = sessions[requestData.cookies._] = session(requestData.cookies._)
	}
	
	sess.set(time(), requestData.url)
	reply("the page you are on is "+requestData.url)
}
port {
	return 80
}







out(
out v {

if a == "hello", "hi" {
	out(a);
}

out v {
	echo v;
}


code 
	must start with:
	statement or function validSequence, 
	statement = scopeDeclarations,
	structure = structureNames



on parsecode
	ret = array(code => array, functions=> array)
		go though each line
			if statement - function call or variable declaration
				ret[code][] = array('type' => 'statement', 'code' => parsestatement(line))
			if structure - if for
				ret[code][] = array('type' => 'if', 'condition' => parsecondition(condline) 'code' => parsecode(lines))
			if function 
				ret[functions][] = array('name' => name, 'args' => parseargs(args), 'code' => parsecode(lines)

	return ret
	







