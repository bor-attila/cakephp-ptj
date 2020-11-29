//the default p function
// p(string key, function mutator, default);
window.__phptojavascript.user = {
    firtname: "John",
    lastname: "Connor",
    age: 15
};

p('not_exists'); // undefined

p('user', null, false); // { .... }

p('not_exists', null, false); // false

p('user'); // Object { firtname: "John", lastname: "Connor", age: 15 }
p('user', user => user.age); // 15

// from version 1.1, the second parameter can be a default value or a mutator
// p(string key, any default);

// if the requested key WAS NOT found
//
// 1. then the second parameter is returned if there is only 2 parameter given
console.assert(p('not_exists', false) === false);
// 2. then the second parameter is ignored, and the third parameter is returned if there is 3 parameter or more
console.assert(p('not_exists', 'ignored', false) === false);
// 3. then the default result is undefined
console.assert(typeof p('not_exists') === 'undefined');

// if the requested key WAS found
//
// 1. then the value stored on the PHP side will be returned
console.assert(p('user') === window.__phptojavascript.user);
// 2. and the second parameter is a function, then the result will be 'mutated' by the function
console.assert(p('user', x => x.age) === 15);
// 3. and the second parameter is not a function, then the value stored on the PHP side will be returned and the other
// parameters are ignored
console.assert(p('user', 'not a function, so this is ignored', 'default value, but ignored') === window.__phptojavascript.user);
