var VELOCITY = 0.18;
var COUNT =50;

var PERCENT_COVERAGE = 6;
var PERCENT_SINGLESQUARES_INIT =50;

var sideLen = 3; //cik px gara mala

var particles = [];
var ornaments = [];

var colors = ["#FFCF00", "#36C038", "#24BADF", "#004D9E", "#EF4700"];
var symbolFuncs = ["drawZalktis", "drawOrnament1", "drawJumis"];



var canvas = document.getElementById('projector');
var context = canvas.getContext('2d');


if (window.devicePixelRatio > 1) {
    var canvasWidth = canvas.width;
    var canvasHeight = canvas.height;

    canvas.width = canvasWidth * window.devicePixelRatio;
    canvas.height = canvasHeight * window.devicePixelRatio;
    canvas.style.width = canvasWidth;
    canvas.style.height = canvasHeight;

    context.scale(window.devicePixelRatio, window.devicePixelRatio);
}




var xcoo;
var ycoo;

var checkRound = 0;

var isMobile = {
	Android: function() {
		return navigator.userAgent.match(/Android/i);
	},
	BlackBerry: function() {
		return navigator.userAgent.match(/BlackBerry/i);
	},
	iOS: function() {
		return navigator.userAgent.match(/iPhone|iPad|iPod/i);
	},
	Opera: function() {
		return navigator.userAgent.match(/Opera Mini/i);
	},
	Windows: function() {
		return navigator.userAgent.match(/IEMobile/i);
	},
	any: function() {
		return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
	}
};

function getRandomInt(min, max) {
	return Math.floor(Math.random() * (max - min + 1)) + min;
}

if (canvas && canvas.getContext) {
	context = canvas.getContext('2d');

	if (isMobile.any()) {
		sideLen = 2;
		ResizeCanvas();

	}

	//dabūjam cik vajag, lai ir noklāts .X%;
	var vajagPx = (window.innerWidth * window.innerHeight) * (0.001 * PERCENT_COVERAGE);
	COUNT = Math.round(vajagPx / ((sideLen * sideLen) * 2));

	//Trešdaļu ieklājam ar parastiem px
	for (var i = 0; i < Math.round(COUNT * (PERCENT_SINGLESQUARES_INIT * 0.01)); i++) {
		xcoo = 0
		while (xcoo < sideLen) {
			xcoo = Math.random() * (window.innerWidth - sideLen)
		}

		ycoo = 0
		while (ycoo < sideLen) {
			ycoo = Math.random() * (window.innerHeight - sideLen)
		}

		addNewSquare(xcoo, ycoo, 1);
	}

	setTimeout(function() {
		Initialize();
	}, 100);
}

function ResizeCanvas(e) {
		canvas.width = window.innerWidth;
	canvas.height = window.innerHeight;

	if (window.devicePixelRatio > 1) {
    var canvasWidth = canvas.width;
    var canvasHeight = canvas.height;

    canvas.width = canvasWidth * window.devicePixelRatio;
    canvas.height = canvasHeight * window.devicePixelRatio;
    canvas.style.width = canvasWidth;
    canvas.style.height = canvasHeight;

    context.scale(window.devicePixelRatio, window.devicePixelRatio);
}


}

	window.requestAnimationFrame(TimeUpdate);


function Initialize() {
	window.addEventListener('resize', ResizeCanvas, false);
	ResizeCanvas();
}

function addNewSquare(x, y, speedFactor, color, vxs, vys, protect) {
	if (color === undefined) {
		color = colors[Math.floor(Math.random() * colors.length)];
	}
	if (protect === undefined) {
		protect = 0;
	}
	if (vxs === undefined) {
		vxs = ((Math.random() * (VELOCITY * 2)) - VELOCITY) * speedFactor;
	}
	if (vys === undefined) {
		vys = ((Math.random() * (VELOCITY * 2)) - VELOCITY) * speedFactor;
	}

	particles.push({
		x: x,
		y: y,
		vx: vxs,
		vy: vys,
		color: color,
		letitroll: 0,
		protect: protect
	});
}

function getNewRandomOutherCoords() {
	xcoo = getRandomInt(-100, window.innerWidth + 100);
	ycoo = getRandomInt(-100, window.innerHeight + 100);
}

function checkPixcelCount() {
	var len = particles.length;
	//ja par len par maz - uzzimejam kko
	if (len < COUNT) {

		getNewRandomOutherCoords();

		while (
			((xcoo > (sideLen * -5)) && xcoo < window.innerWidth + (sideLen * 5)) &&
			((ycoo > (sideLen * -5)) && ycoo < window.innerHeight + (sideLen * 5))
		) {
			getNewRandomOutherCoords();
		}

		vxs = Math.random() * (VELOCITY * 2);
		if (xcoo > Math.round(window.innerWidth / 2)) vxs = vxs * (-1);

		vys = Math.random() * (VELOCITY * 2);
		if (ycoo > Math.round(window.innerHeight / 2)) vys = vys * (-1);

		var funcName = symbolFuncs[Math.floor(Math.random() * symbolFuncs.length)];
		window[funcName](xcoo, ycoo, sideLen, 1, vxs, vys, 1);
	}
}


function TimeUpdate(e) {

	context.clearRect(0, 0, window.innerWidth, window.innerHeight);

	var len = particles.length;
	var particle;

	for (var i = 0; i < len; i++) {

		particle = particles[i];

		if (particle === undefined) {
			continue;
		}

		particle.x += particle.vx;
		particle.y += particle.vy;

		//X

		if ((particle.x + sideLen > window.innerWidth || particle.x - sideLen < 0) && particle.protect != 1) {

			if (particle.letitroll == 1 || Math.round(Math.random() * 10) < 2) {
				particle.letitroll = 1;
				if (
					(particle.x - sideLen > window.innerWidth) // pa labo sānu
					||
					(particle.x + sideLen < 0) // pa kreiseo sānu
				) {
					particles.splice(i, 1);
					continue;
				}
			} else {
				particle.vx = particle.vx * -1;
			}
		}

		if (particle.protect != 1) {
			if (Math.abs(particle.vx) < VELOCITY) {
				particle.vx *= 1.1;
			} else if (Math.abs(particle.vx) < VELOCITY * 1.1) {
				particle.vx *= (1 + (Math.random() * 2));
			}
		}

		//Y

		if ((particle.y + sideLen > window.innerHeight || particle.y - sideLen < 0) && particle.protect != 1) {
			if (particle.letitroll == 1 || Math.round(Math.random() * 10) < 2) {
				particle.letitroll = 1;
				if (
					(particle.y - sideLen > window.innerHeight) //izlido pa apakšu
					||
					(particle.y + sideLen < 0) //izlido pa augšu
				) {
					particles.splice(i, 1);
					continue;
				}
			} else {
				particle.vy = particle.vy * -1;
			}
		}

		if (particle.protect != 1) {
			if (Math.abs(particle.vy) < VELOCITY) {
				particle.vy *= 1.1;
			} else if (Math.abs(particle.vy) < VELOCITY * 1.1) {
				particle.vy *= (1 + (Math.random() * 2));
			}
		}


		//Protected - izšķīdināšana
		if (
			particle.protect == 1 &&
			(particle.x > 150 && particle.x < (window.innerWidth - 150)) &&
			(particle.y > 150 && particle.y < (window.innerHeight - 150)) &&
			(Math.round(Math.random() * 1000) < 25)
		) {
			particle.protect = 0;
			particle.vx = ((Math.random() * (VELOCITY * 2)) - VELOCITY);
			particle.vy = ((Math.random() * (VELOCITY * 2)) - VELOCITY);
		}

		//Ja tomēr protected aizlido pa tālu - lai nerēķina un beidzas
		if (particle.protect == 1 &&
			(
				(particle.x < -200) ||
				(particle.x > (window.innerWidth + 200)) ||
				(particle.y < -200) ||
				(particle.y > (window.innerHeight + 200))
			)
		) {
			particles.splice(i, 1);
			continue;
		}


		context.strokeStyle = context.fillStyle = particle.color;

		context.beginPath();

		context.moveTo(particle.x - sideLen, particle.y);
		context.lineTo(particle.x, particle.y + sideLen);
		context.lineTo(particle.x + sideLen, particle.y);
		context.lineTo(particle.x, particle.y - sideLen);

		context.closePath();

		context.stroke();
		context.fill();

	}

	//ik pa 25 reizēm pārbaudām vai nav kas jāpiezīmē
	checkRound++;
	if (checkRound == 25) {
		checkPixcelCount();
		checkRound = 0;
	}

	    window.requestAnimationFrame(TimeUpdate);


}

function drawZalktis(x, y, s, speedfactor, vxs, vys, protect) {
	var coords = [{
			x: x,
			y: y
		}, {
			x: x - s,
			y: y - s
		}, {
			x: x - (s * 2),
			y: y - (s * 2)
		}, {
			x: x - (s * 3),
			y: y - s
		}, {
			x: x - (s * 4),
			y: y
		}, {
			x: x - (s * 3),
			y: y + s
		}, {
			x: x - (s * 2),
			y: y + (s * 2)
		},

		{
			x: x + s,
			y: y + s
		}, {
			x: x + (s * 2),
			y: y + (s * 2)
		}, {
			x: x + (s * 3),
			y: y + s
		}, {
			x: x + (s * 4),
			y: y
		}, {
			x: x + (s * 3),
			y: y - s
		}, {
			x: x + (s * 2),
			y: y - (s * 2)
		}

	];

	drawCoords(coords, speedfactor, vxs, vys, protect);

}

function drawOrnament1(x, y, s, speedfactor, vxs, vys, protect) {
	var color1 = colors[Math.floor(Math.random() * colors.length)];
	var color2 = colors[Math.floor(Math.random() * colors.length)];
	var color3 = colors[Math.floor(Math.random() * colors.length)];

	var coords = [{
			x: x - s,
			y: y - (s * 3),
			c: color1
		}, {
			x: x + s,
			y: y - (s * 3),
			c: color1
		},

		{
			x: x,
			y: y - (s * 2),
			c: color1
		},

		{
			x: x - (s * 3),
			y: y - s,
			c: color2
		}, {
			x: x - (s),
			y: y - s,
			c: color3
		}, {
			x: x + (s),
			y: y - s,
			c: color3
		}, {
			x: x + (s * 3),
			y: y - s,
			c: color2
		},

		{
			x: x - (s * 2),
			y: y,
			c: color1
		}, {
			x: x + (s * 2),
			y: y,
			c: color1
		},

		{
			x: x - (s * 3),
			y: y + s,
			c: color3
		}, {
			x: x - (s),
			y: y + s,
			c: color2
		}, {
			x: x + (s),
			y: y + s,
			c: color2
		}, {
			x: x + (s * 3),
			y: y + s,
			c: color3
		},

		{
			x: x,
			y: y + (s * 2),
			c: color1
		},

		{
			x: x - s,
			y: y + (s * 3),
			c: color1
		}, {
			x: x + s,
			y: y + (s * 3),
			c: color1
		}

	];

	drawCoords(coords, speedfactor, vxs, vys, protect);
}

function drawJumis(x, y, s, speedfactor, vxs, vys, protect) {
	var coords = [{
			x: x,
			y: y
		},

		{
			x: x - s,
			y: y + s
		}, {
			x: x - (s * 2),
			y: y + (s * 2)
		}, {
			x: x - (s * 3),
			y: y + (s * 3)
		}, {
			x: x - (s * 4),
			y: y + (s * 4)
		}, {
			x: x - (s * 5),
			y: y + (s * 5)
		},


		{
			x: x - s,
			y: y - s
		}, {
			x: x - (s * 2),
			y: y - (s * 2)
		}, {
			x: x - (s * 3),
			y: y - (s * 3)
		}, {
			x: x - (s * 4),
			y: y - (s * 4)
		}, {
			x: x - (s * 5),
			y: y - (s * 3)
		}, {
			x: x - (s * 6),
			y: y - (s * 2)
		},


		{
			x: x + s,
			y: y + s
		}, {
			x: x + (s * 2),
			y: y + (s * 2)
		}, {
			x: x + (s * 3),
			y: y + (s * 3)
		}, {
			x: x + (s * 4),
			y: y + (s * 4)
		}, {
			x: x + (s * 5),
			y: y + (s * 5)
		},


		{
			x: x + s,
			y: y - s
		}, {
			x: x + (s * 2),
			y: y - (s * 2)
		}, {
			x: x + (s * 3),
			y: y - (s * 3)
		}, {
			x: x + (s * 4),
			y: y - (s * 4)
		}, {
			x: x + (s * 5),
			y: y - (s * 3)
		}, {
			x: x + (s * 6),
			y: y - (s * 2)
		}

	];

	drawCoords(coords, speedfactor, vxs, vys, protect);
}

function drawCoords(coords, speedfactor, vxs, vys, protect) {
	var reizes = coords.length;
	for (var i = 0; i < reizes; i++) {

		if (coords[i].c === undefined) {
			coords[i].c = colors[Math.floor(Math.random() * colors.length)];
		}

		coords[i].protect = 0;
		if (coords[i].x < 0 || coords[i].y < 0) {
			coords[i].protect = 1;
		}

		addNewSquare(coords[i].x, coords[i].y, speedfactor, coords[i].c, vxs, vys, protect);
	}
}


