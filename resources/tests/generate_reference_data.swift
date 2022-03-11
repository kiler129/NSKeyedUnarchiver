import Foundation

let prefix = "./ArchivedData/";

let sharedObject = NSURL(string: "https://example.com/foo/bar?a=b#hash");
let setsToSerialize: [String: Any?] = [
    "null": nil,
    "emptyObject": TestDummy(props: [:]),
    "emptyObjectExtended": TestDummyExtended(props: [:]),
    "objectWithSingleProp": TestDummy(props: ["foo": "bar"]),
    "date": ISO8601DateFormatter().date(from: "2039-05-12T20:05:12Z"),
    "UUIDv4": UUID(),
    "scalarTypes": TestDummy(props: [
        "typeBool": true,
        "typeInt": 42,
        "typeFloat": 3.14,
        "typeString": "foo bar",
        "data": "foobar".data(using: String.Encoding.utf8),
    ]),
    "collections": TestDummy(props: [
       "arrayOfStrings": ["foo", "bar", "baz"],
       "arrayOfInts": [1,2,3],
       "dictionary": ["foo": "bar", "baz": "aaa"],
       "set": ["set", "is", "not", "ordered"] as Set,
    ]),
    "miscTypes": TestDummy(props: [ //A catch-all for simple enough types but not scalars nor collections
        "url": NSURL(string: "https://example.com/foo/bar?a=b#hash"),
        "regex": try NSRegularExpression(pattern: #"(.*)"#),
    ]),
    "nested": [
        "level": 0,
        "levelPath": ["zero"],
        "levelZeroData": [
            "level": 1,
            "levelPath": ["zero", "one"],
            "levelOneData": [
                "level": 2,
                "levelPath": ["zero", "one", "two"],
                "sampleUrl": NSURL(string: "https://example.com/foo/bar?a=b#hash")!
            ]
        ]
    ],
    "reference": [
        "copyOne": sharedObject,
        "copyTwo": sharedObject
    ]
];

//Dummy universal class which can encode any properties as if it had real properties
class TestDummy: NSObject, NSCoding {
    var fakeProperties: [String: Any?] = [:]
    func encode(with coder: NSCoder) {
        for (k,v) in fakeProperties { coder.encode(v, forKey: k) }
    }
    required init?(coder: NSCoder) {}
    init(props: [String: Any?]) { fakeProperties = props }
}

//Chains of classes are only added for Objective-C objects
@objc(TestDummyExtended)
class TestDummyExtended: TestDummy {}

func archiveToFile(
    prefix: String,
    name: String,
    format: PropertyListSerialization.PropertyListFormat,
    data: Any?
) -> Void {
    let archiver = NSKeyedArchiver.init(requiringSecureCoding: false)

    //Actual archival process with dummy class having a stable name
    archiver.setClassName("TestDummy", for: TestDummy.self)
    archiver.setClassName("TestDummyExtended", for: TestDummyExtended.self)

    //Encode the data
    archiver.outputFormat = format
    archiver.encode(data, forKey: NSKeyedArchiveRootObjectKey)
    archiver.finishEncoding()

    //Save archived data
    let path = prefix + name + ".plist"
    try! archiver.encodedData.write(to: URL.init(fileURLWithPath: path));
    print("Archived \"\(name)\" to \(path)")
}

for (file, data) in setsToSerialize {
    archiveToFile(
        prefix: prefix,
        name: file + ".bin",
        format: PropertyListSerialization.PropertyListFormat.binary,
        data: data
    )

    archiveToFile(
        prefix: prefix,
        name: file + ".xml",
        format: PropertyListSerialization.PropertyListFormat.xml,
        data: data
    )
}

print("All OK")

