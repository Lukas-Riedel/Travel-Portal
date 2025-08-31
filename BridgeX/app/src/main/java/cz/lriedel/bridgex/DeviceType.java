package cz.lriedel.bridgex;

public enum DeviceType {
    PORTAL("portal"),
    BRIDGEX("bridgex");

    private final String value;

    DeviceType(String value) {
        this.value = value;
    }

    public String getValue() {
        return value;
    }
}
